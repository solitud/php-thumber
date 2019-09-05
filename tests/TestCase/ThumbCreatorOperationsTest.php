<?php
/**
 * This file is part of php-thumber.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/php-thumber
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Tools\Thumber\Test\TestCase;

use Intervention\Image\Exception\InvalidArgumentException;
use Tools\Thumber\TestSuite\TestCase;

/**
 * ThumbCreatorOperationsTest class
 */
class ThumbCreatorOperationsTest extends TestCase
{
    /**
     * Test for `crop()` method
     * @ŧest
     */
    public function testCrop()
    {
        $thumb = $this->getThumbCreatorInstance()->crop(200, 200)->save();
        $this->assertImageSize(200, 200, $thumb);

        //Only width
        $thumb = $this->getThumbCreatorInstance()->crop(200)->save();
        $this->assertImageSize(200, 200, $thumb);

        //In this case, the width will be the original size
        $thumb = $this->getThumbCreatorInstance()->crop(400, 200)->save();
        $this->assertImageSize(400, 200, $thumb);

        //Using `x` and `y` options
        $thumb = $this->getThumbCreatorInstance()->crop(200, 200, ['x' => 50, 'y' => 50])->save();
        $this->assertImageSize(200, 200, $thumb);

        //Without parameters
        $this->expectException(InvalidArgumentException::class);
        $this->getThumbCreatorInstance()->crop()->save();
    }

    /**
     * Test for `fit()` method
     * @ŧest
     */
    public function testFit()
    {
        $thumb = $this->getThumbCreatorInstance()->fit(200)->save();
        $this->assertImageSize(200, 200, $thumb);

        $thumb = $this->getThumbCreatorInstance()->fit(200, 400)->save();
        $this->assertImageSize(200, 400, $thumb);

        //Using the `position` option
        $thumb = $this->getThumbCreatorInstance()->fit(200, 200, ['position' => 'top'])->save();
        $this->assertImageSize(200, 200, $thumb);

        //Using the `upsize` option
        //In this case, the thumbnail will keep the original dimensions
        $thumb = $this->getThumbCreatorInstance()->fit(450, 450, ['upsize' => true])->save();
        $this->assertImageSize(400, 400, $thumb);

        //Using the `upsize` option
        //In this case, the thumbnail will exceed the original size
        $thumb = $this->getThumbCreatorInstance()->fit(450, 450, ['upsize' => false])->save();
        $this->assertImageSize(450, 450, $thumb);

        //Using the `upsize` option
        //In this case, the thumbnail will exceed the original size
        $thumb = $this->getThumbCreatorInstance()->fit(null, 450, ['upsize' => false])->save();
        $this->assertImageSize(450, 450, $thumb);

        //Without parameters
        $this->expectException(InvalidArgumentException::class);
        $this->getThumbCreatorInstance()->fit()->save();
    }

    /**
     * Test for `resize()` method
     * @ŧest
     */
    public function testResize()
    {
        $thumb = $this->getThumbCreatorInstance()->resize(200)->save();
        $this->assertImageSize(200, 200, $thumb);

        $thumb = $this->getThumbCreatorInstance()->resize(null, 200)->save();
        $this->assertImageSize(200, 200, $thumb);

        //Using  the `aspectRatio` option
        //In this case, the thumbnail will keep the ratio
        $thumb = $this->getThumbCreatorInstance()->resize(200, 300, ['aspectRatio' => true])->save();
        $this->assertImageSize(200, 200, $thumb);

        //Using  the `aspectRatio` option
        //In this case, the thumbnail will not maintain the ratio
        $thumb = $this->getThumbCreatorInstance()->resize(200, 300, ['aspectRatio' => false])->save();
        $this->assertImageSize(200, 300, $thumb);

        //Using the `upsize` option
        //In this case, the thumbnail will keep the original dimensions
        $thumb = $this->getThumbCreatorInstance()->resize(450, 450, ['upsize' => true])->save();
        $this->assertImageSize(400, 400, $thumb);

        //Using the `upsize` option
        //In this case, the thumbnail will exceed the original size
        $thumb = $this->getThumbCreatorInstance()->resize(450, 450, ['upsize' => false])->save();
        $this->assertImageSize(450, 450, $thumb);

        //Using the `upsize` option
        //In this case, the thumbnail will exceed the original size
        $thumb = $this->getThumbCreatorInstance()->resize(null, 450, ['upsize' => false])->save();
        $this->assertImageSize(450, 450, $thumb);

        //Using `aspectRatio` and `upsize` options
        //In this case, the thumbnail will keep the ratio and the original dimensions
        $thumb = $this->getThumbCreatorInstance()->resize(500, 600, [
            'aspectRatio' => true,
            'upsize' => true,
        ])->save();
        $this->assertImageSize(400, 400, $thumb);

        //Using `aspectRatio` and `upsize` options
        //In this case, the thumbnail will not keep the ratio and the original dimensions
        $thumb = $this->getThumbCreatorInstance()->resize(500, 600, [
            'aspectRatio' => false,
            'upsize' => false,
        ])->save();
        $this->assertImageSize(500, 600, $thumb);

        //Using `aspectRatio` and `upsize` options
        //In this case, the thumbnail will not keep the ratio and the original dimensions
        $thumb = $this->getThumbCreatorInstance()->resize(null, 600, [
            'aspectRatio' => false,
            'upsize' => false,
        ])->save();
        $this->assertImageSize(400, 600, $thumb);

        //Without parameters
        $this->expectException(InvalidArgumentException::class);
        $this->getThumbCreatorInstance()->resize()->save();
    }

    /**
     * Test for `resizeCanvas()` method
     * @ŧest
     */
    public function testResizeCanvas()
    {
        $thumb = $this->getThumbCreatorInstance()->resizeCanvas(200, 100)->save();
        $this->assertImageSize(200, 100, $thumb);

        $thumb = $this->getThumbCreatorInstance()->resizeCanvas(null, 200)->save();
        $this->assertImageSize(400, 200, $thumb);

        //Using the `anchor` option
        $thumb = $this->getThumbCreatorInstance()->resizeCanvas(300, 300, ['anchor' => 'bottom'])->save();
        $this->assertImageSize(300, 300, $thumb);

        //Using `relative` and `bgcolor` options
        $thumb = $this->getThumbCreatorInstance()
            ->resizeCanvas(300, 300, ['relative' => true, 'bgcolor' => '#000000'])
            ->save();
        $this->assertImageSize(700, 700, $thumb);
    }

    /**
     * Test for several methods called in sequence on the same image (eg.,
     *  `crop()` and `resize()`
     * @test
     */
    public function testSeveralMethods()
    {
        $thumb = $this->getThumbCreatorInstance()->crop(600)->resize(200)->save();
        $this->assertImageSize(200, 200, $thumb);
    }
}
