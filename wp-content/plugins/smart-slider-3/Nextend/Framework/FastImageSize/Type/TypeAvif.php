<?php

/**
 * fast-image-size image type avif
 *
 * @package       fast-image-size
 */

namespace Nextend\Framework\FastImageSize\Type;

use Nextend\Framework\FastImageSize\FastImageSize;

class TypeAvif extends TypeBase {

    /** @var int Amount of bytes read to look for the ispe box */
    const AVIF_HEADER_SIZE = 512;

    /**
     * Constructor for avif image type. Adds missing constant if necessary.
     *
     * @param FastImageSize $fastImageSize
     */
    public function __construct(FastImageSize $fastImageSize) {
        parent::__construct($fastImageSize);

        if (!defined('IMAGETYPE_AVIF')) {
            define('IMAGETYPE_AVIF', 19);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($filename) {
        $size = $this->getSizeNative($filename);

        if ($size === false) {
            $size = $this->getSizeFromIspe($filename);
        }

        if ($size === false) {
            return;
        }

        $this->fastImageSize->setSize($size);
        $this->fastImageSize->setImageType(IMAGETYPE_AVIF);
    }

    /**
     * Try to read the dimensions through the native getimagesize() function.
     * PHP supports AVIF in getimagesize() from 8.1, which is the same version
     * that ships the AVIF encoder, so generated AVIF files are covered here.
     *
     * @param string $filename
     *
     * @return array|bool Size array or false on failure
     */
    protected function getSizeNative($filename) {
        if (!function_exists('getimagesize')) {
            return false;
        }

        $info = @getimagesize($filename);
        if ($info !== false && !empty($info[0]) && !empty($info[1])) {
            return array(
                'width'  => $info[0],
                'height' => $info[1]
            );
        }

        return false;
    }

    /**
     * Fallback that parses the ISO base media file format "ispe" box which
     * stores the image spatial extents (width and height) as big-endian
     * unsigned 32-bit integers.
     *
     * @param string $filename
     *
     * @return array|bool Size array or false on failure
     */
    protected function getSizeFromIspe($filename) {
        $data = $this->fastImageSize->getImage($filename, 0, self::AVIF_HEADER_SIZE, false);

        if ($data === false) {
            return false;
        }

        $position = strpos($data, 'ispe');
        if ($position === false || strlen($data) < $position + 12) {
            return false;
        }

        // 4 bytes box name + 4 bytes version/flags, then width and height.
        $dimensions = unpack('Nwidth/Nheight', substr($data, $position + 8, 8));

        if (empty($dimensions['width']) || empty($dimensions['height'])) {
            return false;
        }

        return array(
            'width'  => $dimensions['width'],
            'height' => $dimensions['height']
        );
    }
}
