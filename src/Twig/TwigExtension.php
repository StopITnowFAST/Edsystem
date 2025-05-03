<?php 

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    public function __construct(
        private ProfilePhotoRuntime $runtime
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('profile_photo', [$this->runtime, 'getProfilePhoto']),
        ];
    }
}