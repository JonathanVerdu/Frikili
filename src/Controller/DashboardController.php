<?php

namespace App\Controller;

use App\Entity\Comentarios;
use App\Entity\Posts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;


class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="dashboard")
     */
    public function index(PaginatorInterface $paginator, Request $request)
    {
        // Todos los posts paginados
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository(Posts::class)->BuscarTodosLosPosts();
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            2 /*limit per page*/
        );

        // Todos los comentarios del usuario
        if($this->getUser() != null) {
            $comentarios = $em->getRepository(Comentarios::class)->ComentariosDelUsuario($this->getUser()->getId());
        }else{
            $comentarios = "";
        }

        return $this->render('dashboard/index.html.twig', [
            'pagination' => $pagination,
            'comentarios' => $comentarios,
        ]);
    }
}
