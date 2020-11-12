<?php

namespace App\Controller;

use App\Entity\Comentarios;
use App\Entity\Posts;
use App\Form\ComentariosType;
use App\Form\PostsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostsController extends AbstractController
{
    /**
     * @Route("/registrar-posts", name="RegistrarPosts")
     */
    public function index(Request $request, SluggerInterface $slugger)
    {
        $post = new Posts();
        $form = $this->createForm(PostsType::class, $post);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){

            $photosFile = $form->get('foto')->getData();
            if ($photosFile) {
                $originalFilename = pathinfo($photosFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photosFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $photosFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception("¡Ups!, ha ocurrido un error, perdona :(");
                }

                $post->setFoto($newFilename);
            }

            $user = $this->getUser();
            $post->setUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('dashboard');
        }
        return $this->render('posts/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/post/{id}", name="verPosts")
     */
    public function verPost($id, Request $request){
        // Ver el Post
        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository(Posts::class)->find($id);

        // Crear un comentario al post
        $comentario = new Comentarios();
        $form = $this->createForm(ComentariosType::class,$comentario);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $comentario->setUser($this->getUser());
            $comentario->setPosts($post);
            $em->persist($comentario);
            $em->flush();
            $this->addFlash("exito", $comentario::COMENTARIO_EXITOSO);
        }

        // Listar los comentarios del post
        $comentarios = $em->getRepository(Comentarios::class)->MostrarComentarios($id);

        return $this->render('posts/verPost.html.twig', [
            'post'=>$post,
            'comentarios' => $comentarios,
            'formulario' => $form->createView()
        ]);
    }

    /**
     * @Route("/mis-posts", name="MisPosts")
     */
    public function misPosts(){
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $posts = $em->getRepository(Posts::class )->findBy(['user'=>$user]);
        return $this->render('posts/misPosts.html.twig',[
            "posts"=>$posts
        ]);
    }

    /**
     * @Route("/likes", options={"expose"=true}, name="Likes")
     */
    public function like(Request $request){
        if($request->isXmlHttpRequest()){
            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser();
            $id = $request->request->get('id');
            $post = $em->getRepository(Posts::class)->find($id);
            $likes = $post->getLikes();
            $likes .= $user->getId().",";
            $post->setLikes($likes);
            $em->flush();
            return new JsonResponse(["likes"=>$likes]);
        }else{
            throw new \Exception("¿Estás tratando de hackearme?");
        }
    }
}
