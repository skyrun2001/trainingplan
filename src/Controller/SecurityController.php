<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_plan');
        }

        return $this->render('security/login.html.twig', [
            'error'         => $authUtils->getLastAuthenticationError(),
            'last_username' => $authUtils->getLastUsername(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        Security $security
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_plan');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $username = trim($request->request->get('username', ''));
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('confirm', '');

            if (strlen($username) < 3) {
                $error = 'Benutzername muss mindestens 3 Zeichen lang sein.';
            } elseif (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
                $error = 'Benutzername darf nur Buchstaben, Zahlen, _ - . enthalten.';
            } elseif (strlen($password) < 6) {
                $error = 'Passwort muss mindestens 6 Zeichen lang sein.';
            } elseif ($password !== $confirm) {
                $error = 'Passwörter stimmen nicht überein.';
            } elseif ($userRepo->findOneBy(['username' => $username])) {
                $error = 'Benutzername bereits vergeben.';
            } else {
                $user = new User();
                $user->setUsername($username);
                $user->setPassword($hasher->hashPassword($user, $password));
                $em->persist($user);
                $em->flush();

                $security->login($user, 'form_login');
                return $this->redirectToRoute('app_plan');
            }
        }

        return $this->render('security/register.html.twig', ['error' => $error]);
    }
}
