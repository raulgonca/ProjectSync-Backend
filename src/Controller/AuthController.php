<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
    }

    /**
     * Maneja el inicio de sesión a través de la API
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Validar que los campos requeridos estén presentes
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['message' => 'Faltan campos obligatorios (email, password)'], Response::HTTP_BAD_REQUEST);
        }
        
        // Buscar el usuario por email
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);
        
        // Verificar si el usuario existe y la contraseña es correcta
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['message' => 'Credenciales inválidas'], Response::HTTP_UNAUTHORIZED);
        }
        
        // Generar el token JWT
        $token = $this->jwtManager->create($user);
        
        // Crear un array con los datos del usuario para la respuesta
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'cargo' => $user->getCargo()
        ];
        
        return new JsonResponse([
            'message' => 'Inicio de sesión exitoso',
            'token' => $token,
            'user' => $userData
        ], Response::HTTP_OK);
    }

    #[Route(path: '/api/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}