<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\Commentaire;
use App\Form\CommentaireType;
use App\Form\LivreType;
use App\Entity\Reservation;
use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;


final class LivreController extends AbstractController
{
    #[Route('/profile/livres', name: 'livres_list')]
    public function index(LivreRepository $livreRepository):Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $livres = $livreRepository->findAll();

        return $this->render('livre/index.html.twig', [
            'livres' => $livres,
        ]);
    }

    #[Route('/profile/search', name: 'search_livres')]
    public function search(Request $request, LivreRepository $livreRepository):Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $query = $request->query->get('q', ''); 

        $livres = $livreRepository->searchByQuery($query);

        return $this->render('livre/search.html.twig', [
            'livres' => $livres,
            'query' => $query
        ]);
    }

    #[Route('/profile/livre/{id}', name: 'livre_details')]
    public function details(Request $request, Livre $livre, EntityManagerInterface $em, Security $security): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setLivre($livre);
            $commentaire->setUser($security->getUser());
            $commentaire->setDateCommentaire(new \DateTimeImmutable());
            $em->persist($commentaire);
            $em->flush();
            $this->addFlash('success', 'Commentaire ajouté avec succès !');

            $form = $this->createForm(CommentaireType::class, new Commentaire());
        }

        return $this->render('livre/details.html.twig', [
            'livre' => $livre,
            'commentForm' => $form->createView(),
        ]);
    }

    #[Route('/profile/livre/{id}/reserver', name: 'livre_reserver')]
    public function reserver(Livre $livre, EntityManagerInterface $em, Security $security): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $security->getUser();

        /// Vérifier si le livre est déjà réservé avec un statut "reserve"
        $reservationExistante = null;

        foreach ($livre->getReservations() as $reservation) {
            if (in_array($reservation->getStatut(), ['reserve'])) {
                $reservationExistante = $reservation;
                break;
            }
        }

        if ($reservationExistante) {
            // Si une réservation existe déjà, on affiche un message d'erreur
            $this->addFlash('danger', 'Ce livre est déjà réservé pour l\'instant.');

            return $this->redirectToRoute('livres_list');
        }

        // Sinon, on permet à l'utilisateur de réserver le livre
        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setLivre($livre);
        $reservation->setDateReservation(new \DateTimeImmutable());
        $reservation->setStatut('reserve');
        $em->persist($reservation);
        $em->flush();

        $this->addFlash('success', 'Livre réservé avec succès !');

        return $this->redirectToRoute('livres_list');
    }

    #[Route('/librarian/livre/{id}/edit', name: 'livre_edit')]
    public function edit(Livre $livre, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(LivreType::class, $livre);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Livre modifié avec succès !');
        }

        return $this->render('livre/edit.html.twig', [
            'form' => $form->createView(),
            'livre' => $livre,
        ]);
    }

    #[Route('/librarian/livre/{id}/delete', name: 'livre_delete')]
    public function delete(Livre $livre, EntityManagerInterface $em): Response
    {

        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $em->remove($livre);
        $em->flush();

        $this->addFlash('success', 'Livre supprimé avec succès !');
        return $this->redirectToRoute('admin_livres');
    }

    #[Route('/librarian/livre/{id}/confirm_delete', name: 'livre_confirm_delete')]
    public function confirmDelete(Livre $livre): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('livre/delete.html.twig', [
            'livre' => $livre
        ]);
    }
}
