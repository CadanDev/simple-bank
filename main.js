const API_ENDPOINTS = [
	{
		name: 'reset',
		url: '/reset',
		method: 'POST'
	},
	{
		name: 'getBalance',
		url: '/balance',
		method: 'GET'
	},
	{
		name: 'event',
		url: '/event',
		method: 'POST'
	}
]
const logElement = document.getElementById('message-log');

const logger = (message, level) => {
	if(logElement) {
		const logEntry = document.createElement('div');
		logEntry.textContent = `[${level.toUpperCase()}] ${message}`;
		logEntry.className = level;
		logElement.appendChild(logEntry);
		setTimeout(() => {
			logEntry.remove();
		}, 2000);
	}
	console[level](message);
}

const api = async (endpoint, method, data) => {
	const url = API_ENDPOINTS.find(e => e.name === endpoint).url
	const options = {
		method,
		headers: {
			'Content-Type': 'application/json'
		}
	}
	if (data) {
		options.body = JSON.stringify(data)
	}
	const response = await fetch(url, options)
	return response.json()
}

document.addEventListener('DOMContentLoaded', () => {
	const form = document.getElementById('api-form');
	const methodInput = document.getElementById('http-method');
	const urlInput = document.getElementById('api-url');
	const baseInput = document.getElementById('api-base');
	const bodyInput = document.getElementById('api-body');
	const responsePre = document.getElementById('api-response');
	const headersDiv = document.getElementById('api-headers');

	form.addEventListener('submit', async (e) => {
		e.preventDefault();
		const method = methodInput.value;
		let url = urlInput.value;
		const base = (baseInput && baseInput.value.trim()) ? baseInput.value.trim().replace(/\/$/, '') : '';
		if (!url.match(/^https?:\/\//) && base) {
			url = base + (url.startsWith('/') ? url : '/' + url);
		}
		const options = { method, headers: {} };
		if (method !== 'GET' && bodyInput.value.trim()) {
			options.headers['Content-Type'] = 'application/json';
			options.body = bodyInput.value;
		}

		try {
			const res = await fetch(url, options);
			const statusLine = `${res.status} ${res.statusText}`;
			const contentType = res.headers.get('content-type') || '';
			let bodyText = '';
			if (contentType.includes('application/json')) {
				const json = await res.json();
				bodyText = JSON.stringify(json, null, 2);
			} else {
				bodyText = await res.text();
			}
			responsePre.textContent = bodyText;
			headersDiv.innerHTML = `<div><strong>${statusLine}</strong></div>`;
			res.headers.forEach((value, key) => {
				const d = document.createElement('div');
				d.textContent = `${key}: ${value}`;
				headersDiv.appendChild(d);
			});
			logger(`${method} ${url} -> ${statusLine}`, 'info');
		} catch (err) {
			responsePre.textContent = err.toString();
			logger(err.toString(), 'error');
		}
	});

	document.getElementById('preset-reset')?.addEventListener('click', () => {
		methodInput.value = 'POST'; urlInput.value = '/reset'; bodyInput.value = '';
	});
	document.getElementById('preset-balance')?.addEventListener('click', () => {
		methodInput.value = 'GET'; urlInput.value = '/balance?account_id=100'; bodyInput.value = '';
	});
	document.getElementById('preset-event')?.addEventListener('click', () => {
		methodInput.value = 'POST'; urlInput.value = '/event'; bodyInput.value = JSON.stringify({ type: 'deposit', destination: '100', amount: 10 }, null, 2);
	});
});