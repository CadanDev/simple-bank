<?php

// Inicia sessão PHP para persistência de dados da API.
// O Accounts model espera sessão ativa antes de uso.
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
