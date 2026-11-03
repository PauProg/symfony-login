<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController {
  #[Route('/login', name: 'app_login')]
  public function login(AuthenticationUtils $authenticationUtils): Response {
    // Si ya hay sesión no tiene sentido volver a mostrar el login
    if ($this->getUser()) {
      return $this->redirectToRoute('app_dashboard');
    }

    // El firewall guarda el error y el último usuario en la sesión
    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();

    return $this->render('security/login.html.twig', [
      'last_username' => $lastUsername,
      'error'=> $error,
    ]);
  }
}