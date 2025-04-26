<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Repository\CommentaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserController extends AbstractController
{
    #[Route('/profile', name: 'user_profile')]
    public function profile(UserInterface $user, ReservationRepository $reservationRepository, CommentaireRepository $commentaireRepository): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $reservations = $reservationRepository->findBy(['user' => $user]);
        $commentaires = $commentaireRepository->findBy(['user' => $user]);

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'reservations' => $reservations,
            'commentaires' => $commentaires,
        ]);
    }
}
