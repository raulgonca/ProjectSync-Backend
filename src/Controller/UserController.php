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
use Symfony\Component\Serializer\SerializerInterface;

final class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private SerializerInterface $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
    }

    /**
     * Obtiene todos los usuarios
     */
    #[Route('/api/users', name: 'get_all_users', methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        // Si no hay usuarios, devolver un mensaje informativo
        if (empty($users)) {
            return new JsonResponse(['message' => 'No hay usuarios registrados'], Response::HTTP_OK);
        }
        
        // Crear manualmente un array de usuarios
        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'cargo' => $user->getCargo()
            ];
        }
        
        return new JsonResponse(['users' => $usersData], Response::HTTP_OK);
    }

    /**
     * Obtiene un usuario por su ID
     */
    #[Route('/api/users/{id}', name: 'get_user', methods: ['GET'])]
    public function getUserId(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return new JsonResponse(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Crear directamente un objeto con los datos del usuario
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'cargo' => $user->getCargo()
            // Puedes añadir más campos según sea necesario
        ];
        
        return new JsonResponse($userData, Response::HTTP_OK);
    }

    /**
     * Elimina un usuario
     */
    #[Route('/api/users/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return new JsonResponse(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Guardar los datos del usuario antes de eliminarlo
        $userData = [
            'email' => $user->getEmail(),
            'username' => $user->getUsername()
        ];
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'message' => 'Usuario eliminado correctamente',
            'usuario_eliminado' => $userData
        ], Response::HTTP_OK);
    }

    /**
     * Actualiza la información de un usuario
     */
    #[Route('/api/users/{id}', name: 'update_user', methods: ['PUT'])]
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return new JsonResponse(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        
        // Actualiza otros campos según sea necesario
        
        $this->entityManager->flush();
        
        $updatedUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
        
        return new JsonResponse($updatedUser, Response::HTTP_OK, [], true);
    }

    /**
     * Crea un nuevo usuario
     */
    #[Route('/api/users', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Validar que los campos requeridos estén presentes
        if (!isset($data['email']) || !isset($data['username']) || !isset($data['password'])) {
            return new JsonResponse(['message' => 'Faltan campos obligatorios (email, username, password)'], Response::HTTP_BAD_REQUEST);
        }
        
        // Validar formato de email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['message' => 'El formato del email no es válido'], Response::HTTP_BAD_REQUEST);
        }
        
        // Verificar si el email ya existe
        $existingUserEmail = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUserEmail) {
            return new JsonResponse(['message' => 'El email ya está en uso'], Response::HTTP_CONFLICT);
        }
        
        // Verificar si el username ya existe
        $existingUsername = $this->userRepository->findOneBy(['username' => $data['username']]);
        if ($existingUsername) {
            return new JsonResponse(['message' => 'El nombre de usuario ya está en uso'], Response::HTTP_CONFLICT);
        }
        
        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        
        // Encriptar la contraseña antes de guardarla
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $user->setPassword($hashedPassword);
        
        // Asignar un rol por defecto (por ejemplo, ROLE_USER)
        $user->setCargo('ROLE_USER');
        
        // Configurar otros campos opcionales si están presentes
        if (isset($data['name'])) {
            $user->setUsername($data['name']); 
        }
        
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            // Verificar que el usuario se haya guardado correctamente
            if ($user->getId()) {
                // Crear un array con los datos del usuario manualmente
                $userData = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'cargo' => $user->getCargo()
                ];
                
                // Crear respuesta con mensaje de éxito y datos del usuario
                return new JsonResponse([
                    'message' => 'Usuario creado con éxito',
                    'user' => $userData
                ], Response::HTTP_CREATED);
            } else {
                // Si no se pudo obtener el ID, algo salió mal
                return new JsonResponse(['message' => 'Error al crear el usuario: no se pudo obtener el ID'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error al crear el usuario: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
