<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController {
  #[Route('/', name: 'app_home')]
  public function index(): Response {
    // Enviamos variables dinámicas a la plantilla Twig
    return $this->render('main/index.html.twig', [
      'nombre_alumno' => 'Pau Medina Vázquez',
      'descripcion_proyecto' => 'Este proyecto es un entorno de desarrollo aislado en Docker para dominar las bases arquitectónicas de Symfony 7.4 LTS.',
      'fecha_actual' => (new \DateTime())->format('d/m/Y H:i')
    ]);
  }

  #[Route('/acerca-de', name: 'app_about')]
  public function about(): Response {
    return $this->render('main/about.html.twig', [
      'detalle_tecnico' => 'La aplicación utiliza PHP 8.4 en un contenedor CLI y procesa las vistas mediante el motor de plantillas Twig.'
    ]);
  }
}

?>