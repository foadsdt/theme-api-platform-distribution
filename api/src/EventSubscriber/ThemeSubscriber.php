<?php

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Exception\ThemeIsDefaultException;
use App\Exception\ThemeNotFoundException;
use App\Service\ThemeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use App\Entity\Theme;
use Symfony\Component\Serializer\SerializerInterface;

final class ThemeSubscriber implements EventSubscriberInterface
{
    public function __construct(private SerializerInterface $serializer,
                                private ThemeManager        $themeManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.view' => [
                ['createTheme', EventPriorities::PRE_WRITE],
                ['updateTheme', EventPriorities::PRE_VALIDATE],
                ['deleteTheme', EventPriorities::PRE_VALIDATE],
                ['getDefaultTheme', EventPriorities::PRE_VALIDATE],
            ],
        ];
    }

    public function createTheme(ViewEvent $event)
    {
        $request = $event->getRequest();

        if ('POST' !== $request->getMethod() || !($request->getPathInfo() == '/themes')) {
            return;
        }

        $data = $request->getContent();
        $theme = $this->serializer->deserialize($data, Theme::class, 'json');

        $this->themeManager->handleIsDefault($theme);
    }

    /**
     * @throws ThemeNotFoundException
     * @throws ThemeIsDefaultException
     */
    public function updateTheme(ViewEvent $event)
    {
        $request = $event->getRequest();

        if ('PATCH' !== $request->getMethod() || !preg_match('/themes\/\d+$/', $request->getPathInfo())) {
            return;
        }

        $data = $request->attributes->get('data');
        $previousData = $request->attributes->get('previous_data');

        if (!$data || !$previousData) {
            throw new ThemeNotFoundException('Theme Not Found!');
        }

        if ($previousData->getIsDefault() && !$data->getIsDefault()) {
            throw new ThemeIsDefaultException('Cannot update the default theme without setting it as default!');
        }

        $this->themeManager->handleIsDefault($data);

    }

    /**
     * @throws ThemeNotFoundException
     * @throws ThemeIsDefaultException
     */
    public function deleteTheme(ViewEvent $event)
    {
        $request = $event->getRequest();
        if ('DELETE' !== $request->getMethod() || !preg_match('/themes\/\d+$/', $request->getPathInfo())) {
            return;
        }

        $data = $request->attributes->get('data');

        if (!$data) {
            throw new ThemeNotFoundException('Theme Not Found!');
        }

        if ($data->getIsDefault()) {
            throw new ThemeIsDefaultException('Cannot delete the default theme!');
        }

    }

    /**
     * @throws ThemeNotFoundException
     */
    public function getDefaultTheme(ViewEvent $event)
    {
        $request = $event->getRequest();

        if ('GET' !== $request->getMethod() || $request->getPathInfo() !== '/themes/default') {
            return;
        }

        $defaultTheme = $this->themeManager->getDefaultTheme();

        if (!$defaultTheme) {
            throw new ThemeNotFoundException('Default theme not found');
        }

        $data = $this->serializer->serialize($defaultTheme, 'jsonld');

        $response = new JsonResponse($data, Response::HTTP_OK, [], true);

        $event->setResponse($response);
    }
}
