<?php

namespace App\Service;

use App\Entity\Theme;
use Doctrine\ORM\EntityManagerInterface;

class ThemeManager
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function handleIsDefault(Theme $theme): void
    {
        $existingDefaultTheme = $this->em->getRepository(Theme::class)->findOneBy(['isDefault' => true]);

        if ($theme->getIsDefault()) {
            if ($existingDefaultTheme && $existingDefaultTheme !== $theme) {
                $existingDefaultTheme->setIsDefault(false);
            }
        } else {
            if (!$existingDefaultTheme) {
                $theme->setIsDefault(true);
            }
        }

        $this->em->flush();
    }

    public function getDefaultTheme(): ?Theme
    {
        return $this->em->getRepository(Theme::class)->findOneBy(['isDefault' => true]);
    }
}
