<?php

namespace App\Security\WebserviceVus;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class VusAuthenticator extends VusAuthenticatorBase
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
        // $password = $request->request->get('_password', '');
        // $username = mb_strtolower($request->request->get('_username', ''));
        // $csrfToken = $request->request->get('_csrf_token');
        // germi
        $data = json_decode($request->getContent(), true);

        $username = mb_strtolower($data['username'] ?? '');
        $password = $data['password'] ?? '';

        // Debugging
        // echo "Username: $username, Password: $password"; die;
        $authenticationService = $this->authenticationService; // capture in outer scope

        try {
            return new Passport(
                new UserBadge($username),
                new CustomCredentials(
                    function ($password, UserInterface $user) use ($authenticationService) {
                        try {
                            $userInfo = $authenticationService->login($user->getUserIdentifier(), $password);

                            // $this->$userInfo = $userInfo;

                            if (!$userInfo) {
                                throw new AuthenticationException('Invalid login');
                            }
                    
                            return true;
                        }
                        catch (InvalidVusUserCredentialsException $e) {
                            // Wrap it so onAuthenticationFailure can see it
                            throw new AuthenticationException($e->getMessage(), 0, $e);
                        }
                    },
                    $password
                ),
                // [new CsrfTokenBadge('authenticate', $csrfToken)]
            );
        } 
        // germi
        // catch (InvalidVusUserCredentialsException $e) {
        //     echo "here";die;
        //     throw new AuthenticationException($e->getMessage(), 0, $e);
        // }
        catch (Throwable $exception) {
                throw new AuthenticationException();
            }
        }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        // Get user data from $this->userInfo and update user entity if needed.
        // return new RedirectResponse('/');

        // germi

        $user = $token->getUser();

        return new JsonResponse([
            'token' => $this->jwtManager->create($user),
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        // Returning null to let the request fall into the login controller, so it can render errors.
        // return null;

        

        $previous = $exception->getPrevious();

        if ($previous instanceof InvalidVusUserCredentialsException) {
            return new JsonResponse([
                'error' => $exception->getMessage(),
                // $previous->getVusResponse(), is a SimpleXMLElement, so we convert it to string
                // 'vus_response' => (string) $previous->getVusResponse()->asXML(),
                // 'vus_response' => $previous->getVusResponse(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'error' => $exception->getMessage(),
        ], JsonResponse::HTTP_UNAUTHORIZED);
        
    }
}
