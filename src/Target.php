<?php

namespace ShootingTarget;

/**
 * Class Target.
 *
 * @author Patrick Bußmann <patrick.bussmann@bussmann-it.de>
 */
class Target
{
    const DRAW_TYPE_PNG = 'png';
    const DRAW_TYPE_JPEG = 'jpg';
    const DRAW_TYPE_GIF = 'gif';

    /**
     * @var Hit[]
     */
    private $hits;

    /**
     * @var float the diameter of the 10
     */
    private $diameter10;

    /**
     * @var null|float the diameter of the inner 10
     */
    private $diameterInner10;

    /**
     * @var float the spacing between the rings
     */
    private $ringSpacing;

    /**
     * @var int the number of first inner / black ring
     */
    private $firstInnerRing;

    /**
     * @var int the number of ring
     */
    private $ringCount;
	
	/**
	 * @var array some options like color settings
	 */
    private $options;
	
	/**
	 * Target constructor.
	 *
	 * @param float $diameter10
	 * @param float|null $diameterInner10
	 * @param float $ringSpacing
	 * @param int $firstInnerRing
	 * @param int $ringCount
	 * @param Hit[] $hits
	 * @param array $options
	 */
    public function __construct($diameter10 = 0.5, $diameterInner10 = 0.5, $ringSpacing = 2.5, $firstInnerRing = 4, $ringCount = 10, $hits = [], $options = [])
    {
        $this->diameter10 = $diameter10;
        $this->diameterInner10 = $diameterInner10;
        $this->ringSpacing = $ringSpacing;
        $this->firstInnerRing = $firstInnerRing;
        $this->ringCount = $ringCount;
        $this->setHits($hits);
        $this->setOptions($options);
    }

    /**
     * Draws this target.
     *
     * @param int        $unit     the unit of the calculation conversion
     * @param string     $type     the type of output: PNG, JPEG, GIF
     * @param int|string $font     numeric font is for default fonts and string as font is a TrueType font file path
     * @param string     $filename [optional] <p>
     *                             The path to save the file to. If not set or &null;, the raw image stream
     *                             will be outputted directly.</p>
     *                             <p>&null; is invalid if the quality and
     *                             filters arguments are not used.</p>
     * @param int        $quality  [optional] <p>
     *                             Compression level: from 0 (no compression) to 9.</p>
     * @param int        $filters  [optional] <p>
     *                             Allows reducing the PNG file size. It is a bitmask field which may be
     *                             set to any combination of the PNG_FILTER_XXX
     *                             constants. PNG_NO_FILTER or
     *                             PNG_ALL_FILTERS may also be used to respectively
     *                             disable or activate all filters.</p>
     *
     * @return bool true on success or false on failure
     */
    public function draw($unit = 20, $type = self::DRAW_TYPE_PNG, $font = 5, $filename = null, $quality = null, $filters = null)
    {
        $size = $this->getTargetSize() * $unit;
        $image = imagecreatetruecolor($size, $size);

        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        imagesavealpha($image, true);

        $black = imagecolorallocate($image, 0, 0, 0);
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);

        /**
         * Rings
         */
        for ($x = $this->ringCount - 1; $x >= 0; $x--) {
            $diameter = (($x * $this->ringSpacing * 2) + $this->diameter10) * $unit;

            imagefilledellipse($image, $size / 2, $size / 2, $diameter, $diameter, $x > ($this->ringCount - $this->firstInnerRing) ? $this->hexColorAllocate($image, $this->getOption('outer_ring_border_color')) : $this->hexColorAllocate($image, $this->getOption('inner_ring_border_color')));
            imagefilledellipse($image, $size / 2, $size / 2, $diameter - 3, $diameter - 3, $x > ($this->ringCount - $this->firstInnerRing) ? $this->hexColorAllocate($image, $this->getOption('outer_ring_color')) : $this->hexColorAllocate($image, $this->getOption('inner_ring_color')));

            if ($this->ringCount - $x < 9) {
                $text = $this->ringCount - $x;
                $color = $x > ($this->ringCount - $this->firstInnerRing) ? $this->hexColorAllocate($image, $this->getOption('outer_ring_text_color')) : $this->hexColorAllocate($image, $this->getOption('inner_ring_text_color'));

                if (is_numeric($font)) {
                    $width = (imagefontwidth($font) * strlen($text)) / 2;
                    $height = imagefontheight($font) / 2;

                    /* Text left */
                    imagestring($image, $font, $size / 2 - $width - ($diameter / 2) + $unit * ($this->ringSpacing / 2), $size / 2 - $height, $text, $color);
                    /* Text right */
                    imagestring($image, $font, $size / 2 - $width + ($diameter / 2) - $unit * ($this->ringSpacing / 2), $size / 2 - $height, $text, $color);
                    /* Text top */
                    imagestring($image, $font, $size / 2 - $width, $size / 2 - $height - ($diameter / 2) + $unit * ($this->ringSpacing / 2), $text, $color);
                    /* Text bottom */
                    imagestring($image, $font, $size / 2 - $width, $size / 2 - $height + ($diameter / 2) - $unit * ($this->ringSpacing / 2), $text, $color);
                } else {
                    $bBox = imagettfbbox($unit * 1.25, 0, $font, 10 - $x);
                    $width = ($bBox[2] - $bBox[0]) / 2;
                    $height = ($bBox[1] - $bBox[7]) / 2;

                    /* Text left */
                    imagettftext($image, $unit * 1.25, 0, $size / 2 - $width - ($diameter / 2) + $unit * ($this->ringSpacing / 2), $size / 2 + $height, $color, $font, $text);
                    /* Text right */
                    imagettftext($image, $unit * 1.25, 0, $size / 2 - $width + ($diameter / 2) - $unit * ($this->ringSpacing / 2), $size / 2 + $height, $color, $font, $text);
                    /* Text top */
                    imagettftext($image, $unit * 1.25, 0, $size / 2 - $width, $size / 2 + $height - ($diameter / 2) + $unit * ($this->ringSpacing / 2), $color, $font, $text);
                    /* Text bottom */
                    imagettftext($image, $unit * 1.25, 0, $size / 2 - $width, $size / 2 + $height + ($diameter / 2) - $unit * ($this->ringSpacing / 2), $color, $font, $text);
                }
            }
        }

        /**
         * Inner 10.
         */
        $diameter = ($this->diameterInner10 / 2) * 2 * $unit;
        imagefilledellipse($image, $size / 2, $size / 2, $diameter, $diameter, $this->hexColorAllocate($image, $this->getOption('inner_ring_border_color')));
        if (($this->diameterInner10 / 2) * 2 >= 3) {
            imagefilledellipse($image, $size / 2, $size / 2, $diameter - 2, $diameter - 2, $this->hexColorAllocate($image, $this->getOption('inner_ring_color')));
        }

        foreach ($this->hits as $number => $hit) {
            $x = $hit->getX() * 0.01 * $unit;
            $y = $hit->getY() * 0.01 * $unit;
            $text = $hit->getLabel() ?: ($number + 1);
            $color = $hit->getColor() !== null ? $this->hexColorAllocate($image, $hit->getColor()) : $this->hexColorAllocate($image, $this->getOption('hit_color'));
            $rgbColor = $this->hexColorToRGB($color);

            imagefilledellipse($image, $size / 2 + $x, $size / 2 - $y, 4.5 * $unit, 4.5 * $unit, $this->hexColorAllocate($image, $this->getOption('hit_border_color')));
            imagefilledellipse($image, $size / 2 + $x, $size / 2 - $y, 4.5 * $unit - 3, 4.5 * $unit - 3, $color);

            if (is_numeric($font)) {
                $width = (imagefontwidth($font) * strlen($text)) / 2;
                $height = imagefontheight($font) / 2;

                imagestring($image, $font, $size / 2 + $x - $width, $size / 2 - $y - $height, $text, $rgbColor[3] > 382 ? $black : $white);
            } else {
                $bBox = imagettfbbox($unit * 1.25, 0, $font, $text);
                $width = ($bBox[2] - $bBox[0]) / 2;
                $height = ($bBox[1] - $bBox[7]) / 2;

                imagettftext($image, $unit * 1.25, 0, $size / 2 + $x - $width, $size / 2 - $y + $height, $rgbColor[3] > 382 ? $black : $white, $font, $text);
            }
        }

        if ($type == self::DRAW_TYPE_JPEG) {
            return imagejpeg($image, $filename, $quality);
        } elseif ($type == self::DRAW_TYPE_GIF) {
            return imagegif($image, $filename);
        }

        return imagepng($image, $filename, $quality, $filters);
    }
	
	/**
	 * Get the target size in mm
	 *
	 * @param bool $asDiameter return the diameter if true else the whole size
	 *
	 * @return float
	 */
    public function getTargetSize($asDiameter = false)
    {
    	return (($this->diameter10 / 2) + (($this->ringCount - 1) * $this->ringSpacing)) * ($asDiameter ? 1 : 2);
    }

    /**
     * Get the hits array.
     *
     * @return Hit[]
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Set the hits array.
     *
     * @param Hit[]|Hit $hits
     *
     * @return Target
     */
    public function setHits($hits = [])
    {
        $this->hits = $hits instanceof Hit ? [$hits] : (is_array($hits) ? $hits : []);

        return $this;
    }

    /**
     * Add a hit.
     *
     * @param Hit $hit
     *
     * @return Target
     */
    public function addHit($hit)
    {
        if (!in_array($hit, $this->hits)) {
            $this->hits[] = $hit;
        }

        return $this;
    }

    /**
     * Allocate hex color.
     *
     * @param resource $im
     * @param string   $hex
     *
     * @return int
     */
    private function hexColorAllocate($im, $hex)
    {
        $rgb = $this->hexColorToRGB($hex);

        return imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * Convert hex color to RGB array.
     *
     * @param string $hex
     *
     * @return array
     */
    private function hexColorToRGB($hex)
    {
        $hex = str_repeat(ltrim($hex, '#'), 6);
        $a = hexdec(substr($hex, 0, 2));
        $b = hexdec(substr($hex, 2, 2));
        $c = hexdec(substr($hex, 4, 2));

        return [
            $a,
            $b,
            $c,
            $a + $b + $c,
        ];
    }
	
	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}
	
	/**
	 * Get an option
	 *
	 * @param string $key the key to get
	 * @param mixed $default the default value
	 *
	 * @return mixed if key exist then this or else the default value
	 */
	public function getOption($key, $default = null)
	{
		$key = strtolower($key);
		return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
	}
	
	/**
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->options = array_merge($this->getDefaultOptions(), $options);
	}
	
	/**
	 * @return array default options
	 */
	private function getDefaultOptions()
	{
		return array(
			'outer_ring_color' => '#fff',
			'outer_ring_text_color' => '#000',
			'outer_ring_border_color' => '#000',
			'inner_ring_color' => '#000',
			'inner_ring_text_color' => '#fff',
			'inner_ring_border_color' => '#fff',
			'ring_texts' => true,
			'hit_color' => '#ff0000',
			'hit_border_color' => '#fff'
		);
	}
}
