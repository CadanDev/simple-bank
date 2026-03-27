function showResponse(status, bodyText) {
	document.getElementById('response-meta').textContent = 'Status: ' + status;
	try {
		const json = JSON.parse(bodyText);
		document.getElementById('response-body').textContent = JSON.stringify(json, null, 2);
	} catch (e) {
		document.getElementById('response-body').textContent = bodyText;
	}
}

async function doRequest(method, url, body) {
	const opts = { method, headers: {} };
	if (body !== undefined) {
		opts.headers['Content-Type'] = 'application/json';
		opts.body = JSON.stringify(body);
	}
	const res = await fetch(url, opts);
	const text = await res.text();
	showResponse(res.status, text);
	return { status: res.status, body: text };
}

document.getElementById('btn-reset').addEventListener('click', () => {
	doRequest('POST', '/api/reset');
});

document.getElementById('btn-balance').addEventListener('click', () => {
	const id = document.getElementById('balance-id').value;
	doRequest('GET', '/api/balance?account_id=' + encodeURIComponent(id));
});

document.getElementById('btn-deposit').addEventListener('click', () => {
	const dest = document.getElementById('deposit-dest').value;
	const amount = parseInt(document.getElementById('deposit-amount').value || '0', 10);
	doRequest('POST', '/api/event', { type: 'deposit', destination: dest, amount });
});

document.getElementById('btn-withdraw').addEventListener('click', () => {
	const origin = document.getElementById('withdraw-origin').value;
	const amount = parseInt(document.getElementById('withdraw-amount').value || '0', 10);
	doRequest('POST', '/api/event', { type: 'withdraw', origin, amount });
});

document.getElementById('btn-transfer').addEventListener('click', () => {
	const origin = document.getElementById('transfer-origin').value;
	const destination = document.getElementById('transfer-dest').value;
	const amount = parseInt(document.getElementById('transfer-amount').value || '0', 10);
	doRequest('POST', '/api/event', { type: 'transfer', origin, destination, amount });
});

document.getElementById('btn-run-sequence').addEventListener('click', async () => {
	// Executa a sequência de testes do enunciado
	const seq = [
		{ fn: () => doRequest('POST', '/api/reset') },
		{ fn: () => doRequest('GET', '/api/balance?account_id=1234') },
		{ fn: () => doRequest('POST', '/api/event', { type: 'deposit', destination: '100', amount: 10 }) },
		{ fn: () => doRequest('POST', '/api/event', { type: 'deposit', destination: '100', amount: 10 }) },
		{ fn: () => doRequest('GET', '/api/balance?account_id=100') },
		{ fn: () => doRequest('POST', '/api/event', { type: 'withdraw', origin: '200', amount: 10 }) },
		{ fn: () => doRequest('POST', '/api/event', { type: 'withdraw', origin: '100', amount: 5 }) },
		{ fn: () => doRequest('POST', '/api/event', { type: 'transfer', origin: '100', amount: 15, destination: '300' }) },
		{ fn: () => doRequest('POST', '/api/event', { type: 'transfer', origin: '200', amount: 15, destination: '300' }) }
	];

	for (const step of seq) {
		// aguarda cada request para facilitar inspeção
		// eslint-disable-next-line no-await-in-loop
		await step.fn();
		// pequena pausa
		// eslint-disable-next-line no-await-in-loop
		await new Promise(r => setTimeout(r, 300));
	}
});

// Fallback: show simple greeting if loaded directly
showResponse('-', '{ "ready": true }');
