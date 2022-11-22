<?php

namespace BigBrother\utils;

class JavaSkinImage
{
    private PNGParser $utils;
    private bool $existSkinImage = false;

    public function __construct($binary)
    {
        $this->utils = new PNGParser($binary);
        if ($binary !== "") {
            $this->existSkinImage = true;
        }
    }

    public function getSkinImageData(bool $enableDummyImage = false): string
    {
        return base64_encode($this->getRawSkinImageData($enableDummyImage));
    }

    public function getRawSkinImageData(bool $enableDummyImage = false): string
    {
        $data = "";
        if ($this->existSkinImage) {
            for ($height = 0; $height < $this->utils->getHeight(); $height++) {
                for ($width = 0; $width < $this->utils->getWidth(); $width++) {
                    $rgbaData = $this->utils->getRGBA($height, $width);
                    $data .= chr($rgbaData[0]) . chr($rgbaData[1]) . chr($rgbaData[2]) . chr($rgbaData[3]);
                }
            }
        } elseif ($enableDummyImage) {
            $data = str_repeat(" ", 64 * 32 * 4);//dummy data
        }

        return $data;
    }
}