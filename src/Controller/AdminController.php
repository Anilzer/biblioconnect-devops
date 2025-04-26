<?php

namespace App\Controller;

use App\Entity\Livre;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\LivreType;

final class AdminController extends AbstractController
{
    #[Route('/librarian/livres', name: 'admin_livres')]
    public function index(LivreRepository $livreRepo): Response
    {
        $livres = $livreRepo->findAll();

        return $this->render('admin/index.html.twig', [
            'livres' => $livres
        ]);
    }

    #[Route('/librarian/livre/new', name: 'admin_livre_ajout', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $livre = new Livre();
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($livre);
            $em->flush();

            $this->addFlash('success', 'Livre ajouté avec succès !');
            return $this->redirectToRoute('admin_livres');
        }

        return $this->render('livre/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
