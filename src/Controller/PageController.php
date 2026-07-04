<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    #[Route(
        path: '/',
        name: 'app_page'
    )]
    public function index(): Response
    {
        return $this->render('page/index.html.twig');
    }

    #[Route(
        path: '/design-system',
        name: 'app_design_system'
    )]
    public function designSystem(): Response
    {
        return $this->render('page/design-system.html.twig');
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route(
        path: '/test-email',
        name: 'app_test_email',
        env: 'dev'
    )]
    public function testEmail(MailerInterface $mailer): Response
    {
        $email = new Email()
            ->from('hello@example.com')
            ->to('test@example.com')
            ->subject('Test Email from Symfony')
            ->text('This is a test email sent from Symfony and Mailpit.')
            ->html('<p>This is a test email sent from <b>Symfony</b> and <b>Mailpit</b>.</p>');

        $mailer->send($email);

        $this->addFlash('success', 'Email de test envoyé avec succès !');

        return $this->redirectToRoute('app_login');
    }
}
