<?php

namespace App\Security\WebserviceVus;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Throwable;

class VusSingleSignOnAuthenticator extends VusAuthenticatorBase
{
    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $password = $request->request->get('pass');
        $username = str_replace('ops$', '', strtolower($request->request->get('user')));

        try {
            return new Passport(
                new UserBadge($username),
                new CustomCredentials(
                    function ($password, UserInterface $user) {
                        $this->userInfo = $this->authenticationService->login($user->getUserIdentifier(), $password);
                        return true;
                    },
                    $password
                ),
                []
            );
        } catch (Throwable $exception) {
            throw new AuthenticationException();
        }
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Get user data from $this->userInfo and update user entity if needed.
        return new RedirectResponse('/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Returning null to let the request fall into the login controller, so it can render errors.
        return null;
    }
}
