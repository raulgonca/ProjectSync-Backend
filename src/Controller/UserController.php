<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api', name: 'api_users_')]
final class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Obtiene todos los usuarios (con paginación opcional)
     */
    #[Route('/users', name: 'get_all', methods: ['GET'])]
    public function getAllUsers(Request $request): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 50)));
        $offset = ($page - 1) * $limit;

        $users = $this->userRepository->findBy([], null, $limit, $offset);
        $total = $this->userRepository->count([]);

        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'cargo' => $user->getCargo()
            ];
        }

        return new JsonResponse([
            'data' => $usersData,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Obtiene un usuario por su ID
     */
    #[Route('/users/{id}', name: 'get', methods: ['GET'])]
    public function getUserId(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'cargo' => $user->getCargo()
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Elimina un usuario
     */
    #[Route('/deleteusers/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $userData = [
            'email' => $user->getEmail(),
            'username' => $user->getUsername()
        ];

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Usuario eliminado correctamente',
            'data' => $userData
        ], Response::HTTP_OK);
    }

    /**
     * Actualiza la información de un usuario
     */
    #[Route('/updateusers/{id}', name: 'update', methods: ['PUT'])]
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(['message' => 'El formato del email no es válido'], Response::HTTP_BAD_REQUEST);
            }
            $existingUserEmail = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUserEmail && $existingUserEmail->getId() !== $user->getId()) {
                return new JsonResponse(['message' => 'El email ya está en uso'], Response::HTTP_CONFLICT);
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['username'])) {
            $existingUsername = $this->userRepository->findOneBy(['username' => $data['username']]);
            if ($existingUsername && $existingUsername->getId() !== $user->getId()) {
                return new JsonResponse(['message' => 'El nombre de usuario ya está en uso'], Response::HTTP_CONFLICT);
            }
            $user->setUsername($data['username']);
        }

        if (isset($data['cargo'])) {
            $user->setCargo($data['cargo']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Usuario actualizado correctamente',
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'cargo' => $user->getCargo()
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Crea un nuevo usuario
     */
    #[Route('/newusers', name: 'create', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['username']) || !isset($data['password'])) {
            return new JsonResponse(['message' => 'Faltan campos obligatorios (email, username, password)'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['message' => 'El formato del email no es válido'], Response::HTTP_BAD_REQUEST);
        }

        $existingUserEmail = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUserEmail) {
            return new JsonResponse(['message' => 'El email ya está en uso'], Response::HTTP_CONFLICT);
        }

        $existingUsername = $this->userRepository->findOneBy(['username' => $data['username']]);
        if ($existingUsername) {
            return new JsonResponse(['message' => 'El nombre de usuario ya está en uso'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $user->setCargo(isset($data['cargo']) ? $data['cargo'] : 'ROLE_USER');

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Usuario creado con éxito',
                'data' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'cargo' => $user->getCargo()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error al crear el usuario: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
