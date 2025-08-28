<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class Hash
{
    /**
     * Cria um hash de senha usando o algoritmo Bcrypt.
     * @param string $password A senha em texto plano.
     * @return string O hash da senha.
     */
    public static function make($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verifica se uma senha corresponde a um hash.
     * @param string $password A senha em texto plano.
     * @param string $hash O hash para comparar.
     * @return bool True se a senha for válida, false caso contrário.
     */
    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }
}