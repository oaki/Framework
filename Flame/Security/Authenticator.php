<?php

namespace Flame\Security;

use Nette\Security as NS;


/**
 * Users authenticator.
 */
class Authenticator extends \Nette\Object implements NS\IAuthenticator
{
	private $userFacade;

	public function __construct(\Flame\Models\Users\UserFacade $usersFacade)
	{
		$this->userFacade = $usersFacade;
	}

	/**
	 * Performs an authentication
	 * @param  array
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
	    $user = $this->userFacade->getByUsername($username);

	    if (!$user) {
	        throw new NS\AuthenticationException("User '$username' not found.", self::IDENTITY_NOT_FOUND);
	    }

	    if ($user->password !== $this->calculateHash($password)) {
	        throw new NS\AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
	    }

	    $userData = $user->toArray();
        unset($userData['password']);
	    return new NS\Identity($user->id, $user->role, $userData);
	}



	/**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	public function calculateHash($password)
	{
        //dump(hash('sha512', $password));exit;
		return hash('sha512', $password);
	}

    public function setPassword(\Flame\Models\Users\User $user, $password)
    {
        $user->setPassword($this->calculateHash($password));
        $this->userFacade->persist($user);
    }

}