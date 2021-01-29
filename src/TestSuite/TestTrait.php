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
namespace Thumber\TestSuite;

use Thumber\ThumbCreator;
use Tools\Filesystem;

/**
 * TestCase trait
 */
trait TestTrait
{
    /**
     * Internal method to create a copy of an image file
     * @param string $path Image file path
     * @return string
     */
    protected static function createCopy($path)
    {
        $Filesystem = new Filesystem();
        $result = $Filesystem->createTmpFile();
        $Filesystem->copy($path, $result, true);

        return $result;
    }

    /**
     * Internal method to create some thumbs
     * @return void
     * @uses getThumbCreatorInstance()
     */
    protected function createSomeThumbs()
    {
        $this->getThumbCreatorInstance('400x400.jpg')->resize(200)->save();
        $this->getThumbCreatorInstance('400x400.jpg')->resize(300)->save();
        $this->getThumbCreatorInstance('400x400.png')->resize(200)->save();
    }

    /**
     * Asserts that the contents of one image file is equal to the contents of
     *  another image file
     * @param string $expected Expected file
     * @param string $actual Actual file
     * @param string $message The failure message that will be appended to the
     *  generated message
     * @return void
     * @uses createCopy()
     */
    public static function assertImageFileEquals($expected, $actual, $message = '')
    {
        $Filesystem = new Filesystem();
        $expected = $Filesystem->makePathAbsolute($expected, THUMBER_COMPARING_DIR);
        self::assertFileExists($expected, $message);
        self::assertFileExists($actual, $message);

        $expectedCopy = self::createCopy($expected);
        $actualCopy = self::createCopy($actual);
        self::assertFileEquals($expectedCopy, $actualCopy, $message);

        $Filesystem->remove([$expectedCopy, $actualCopy]);
    }

    /**
     * Asserts for a valid thumbnail path
     * @param string $path Thumbnail path
     * @param string $message The failure message that will be appended to the
     *  generated message
     * @return void
     */
    public function assertThumbPath($path, $message = '')
    {
        $regex = sprintf('/^%s[\w\d_]+\.\w{3,4}/', preg_quote(Filesystem::instance()->addSlashTerm(THUMBER_TARGET), DS));
        self::assertMatchesRegularExpression($regex, $path, $message);
    }

    /**
     * Returns an instance of `ThumbCreator`
     * @param string $path Path of the image from which to create the
     *  thumbnail. It can be a relative path (to APP/webroot/img), a full path
     *  or a remote url
     * @return \Thumber\ThumbCreator
     */
    protected function getThumbCreatorInstance($path = '')
    {
        $path = Filesystem::instance()->makePathAbsolute($path ?: '400x400.jpg', THUMBER_EXAMPLE_DIR);

        return new ThumbCreator($path);
    }

    /**
     * Returns an instance of `ThumbCreator`, after calling `resize()` and
     *  `save()` methods.
     *
     * It can be called passing only the array of options as first argument.
     * @param string $path Path of the image from which to create the thumbnail.
     *  It can be a full path or a remote url
     * @param array $options Options for saving
     * @return \Thumber\ThumbCreator
     * @uses getThumbCreatorInstance()
     */
    protected function getThumbCreatorInstanceWithSave($path = '', array $options = [])
    {
        $thumbCreator = $this->getThumbCreatorInstance($path);
        $thumbCreator->resize(200)->save($options);

        return $thumbCreator;
    }

    /**
     * Skips the test if you running the designated driver
     * @param string $driver Driver name
     * @param string $message The message to display
     * @return bool
     */
    public function skipIfDriverIs($driver, $message = '')
    {
        return parent::skipIf($driver == THUMBER_DRIVER, $message);
    }
}
