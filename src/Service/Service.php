<?php

namespace Survos\LocationBundle\Service;

use Survos\LocationBundle\Entity\Car;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorBagInterface;

class Service
{

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var TokenStorageInterface
     */
    protected $token;

    /**
     * @var RequestStack
     */
    protected $request;

    /**
     * @var TranslatorBagInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $bar;

    /**
     * @var integer
     */
    protected $integerFoo;

    /**
     * @var integer
     */
    protected $integerBar;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $token,
        RequestStack $requestStack,
        TranslatorBagInterface $translator,
        $bar,
        $integerFoo,
        $integerBar
    ) {
        $this->em = $em;
        $this->token = $token;
//        $this->request = $requestStack->getCurrentRequest();
        $this->translator = $translator;
        $this->bar = $bar;
        $this->integerFoo = (int) $integerFoo;
        $this->integerBar = (int) $integerBar;
    }

}
