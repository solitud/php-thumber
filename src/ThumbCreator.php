<?php
declare(strict_types=1);

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
namespace Thumber;

use Intervention\Image\Constraint;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Symfony\Component\Filesystem\Exception\IOException;
use Thumber\Exception\NotReadableImageException;
use Thumber\Exception\UnsupportedImageTypeException;
use Tools\Exception\NotWritableException;
use Tools\Exceptionist;
use Tools\Filesystem;

/**
 * Utility to create a thumb.
 *
 * Please, refer to the `README` file to know how to use the utility and to see examples.
 * @see https://github.com/mirko-pagliai/php-thumber/wiki/How-to-use-ThumbCreator-and-create-thumbnails
 */
class ThumbCreator
{
    /**
     * @var \Tools\Filesystem
     */
    protected Filesystem $Filesystem;

    /**
     * @var \Intervention\Image\ImageManager
     */
    public ImageManager $ImageManager;

    /**
     * Arguments that will be used to generate the name of the thumbnail.
     *
     * Every time you call a method that alters the final thumbnail, its arguments must be added to this array,
     *  including the name of that method.
     * @var array
     */
    protected array $arguments = [];

    /**
     * Callbacks that will be called by the `save()` method to create the thumbnail
     * @var callable[]
     */
    protected array $callbacks = [];

    /**
     * Path of the file from which the thumbnail will be generated
     * @var string
     */
    protected string $path;

    /**
     * Path of the generated thumbnail
     * @var string
     */
    protected string $target;

    /**
     * Construct.
     *
     * It sets the file path and extension.
     * @param string $path Path of the image from which to create the thumbnail. It can be a full path or a remote url
     * @throws \Tools\Exception\NotReadableException
     */
    public function __construct(string $path)
    {
        $this->path = is_url($path) ? $path : Exceptionist::isReadable($path);
        $this->Filesystem = new Filesystem();
        $this->ImageManager = new ImageManager(['driver' => THUMBER_DRIVER]);
        $this->arguments[] = $path;
    }

    /**
     * Internal method to get default options for the `save()` method
     * @param array<string, mixed> $options Passed options
     * @param string $path Path to use
     * @return array{format: string, quality: int, target: false|string} Passed options with default options
     */
    protected function getDefaultSaveOptions(array $options, string $path = ''): array
    {
        /** @var array{format: string, quality: int, target: false|string} $options */
        $options += [
            'format' => $this->Filesystem->getExtension($path ?: $this->path) ?: '',
            'quality' => 90,
            'target' => false,
        ];

        //Fixes some formats
        $options['format'] = preg_replace(['/^jpeg$/', '/^tif$/'], ['jpg', 'tiff'], $options['format']) ?: $options['format'];

        return $options;
    }

    /**
     * Gets an `Image` instance
     * @return \Intervention\Image\Image
     * @throws \ErrorException
     * @throws \Thumber\Exception\NotReadableImageException
     * @throws \Thumber\Exception\UnsupportedImageTypeException
     */
    protected function getImageInstance(): Image
    {
        try {
            $ImageInstance = $this->ImageManager->make($this->path);
        } catch (NotReadableException $e) {
            if (str_starts_with($e->getMessage(), 'Unsupported image type')) {
                $type = mime_content_type($this->path) ?: null;
                throw new UnsupportedImageTypeException($type ? 'Image type `' . $type . '` is not supported by this driver' : 'Image type not supported by this driver');
            }
            $path = $this->Filesystem->rtr($this->path) ?: null;
            throw new NotReadableImageException($path ? 'Unable to read image from `' . $path . '`' : 'Unable to read image from file');
        }

        return $ImageInstance;
    }

    /**
     * Crops the image, cutting out a rectangular part of the image.
     *
     * You can define optional coordinates to move the top-left corner of the cutout to a certain position.
     * @param int $width Required width
     * @param int $height Required height. If not specified, the width will be used
     * @param array<string, int> $options Options for the thumbnail
     * @return self
     * @see https://github.com/mirko-pagliai/php-thumber/wiki/How-to-use-ThumbCreator-and-create-thumbnails#crop
     */
    public function crop(int $width, int $height = 0, array $options = []): ThumbCreator
    {
        $height = $height ?: $width;
        $options += ['x' => 0, 'y' => 0];
        Exceptionist::isPositive($width, sprintf('You have to set at least the width for the `%s()` method', __METHOD__));

        $this->arguments[] = [__FUNCTION__, $width, $height, $options];
        $this->callbacks[] = fn(Image $ImageInstance): Image => $ImageInstance->crop(
            $width,
            $height,
            Exceptionist::isInt($options['x'], 'The `x` option must be an integer'),
            Exceptionist::isInt($options['y'], 'The `y` option must be an integer')
        );

        return $this;
    }

    /**
     * Resizes the image, combining cropping and resizing to format image in a smart way. It will find the best fitting
     *  aspect ratio on the current image automatically, cut it out and resize it to the given dimension
     * @param int $width Required width. If not specified, the height will be used
     * @param int $height Required height. If not specified, the width will be used
     * @param array<string, mixed> $options Options for the thumbnail
     * @return self
     * @see https://github.com/mirko-pagliai/php-thumber/wiki/How-to-use-ThumbCreator-and-create-thumbnails#fit
     */
    public function fit(int $width = 0, int $height = 0, array $options = []): ThumbCreator
    {
        $width = $width ?: $height;
        Exceptionist::isPositive($width, sprintf('You have to set at least the width for the `%s()` method', __METHOD__));
        $height = $height ?: $width;
        $options += ['position' => 'center', 'upsize' => true];

        $this->arguments[] = [__FUNCTION__, $width, $height, $options];
        $this->callbacks[] = fn(Image $ImageInstance): Image => $ImageInstance
            ->fit($width, $height, function (Constraint $constraint) use ($options): void {
                if ($options['upsize']) {
                    $constraint->upsize();
                }
            }, $options['position']);

        return $this;
    }

    /**
     * Resizes the image
     * @param int $width Required width
     * @param int $height Required height. If not specified, the width will be used
     * @param array<string, bool> $options Options for the thumbnail
     * @return self
     * @see https://github.com/mirko-pagliai/php-thumber/wiki/How-to-use-ThumbCreator-and-create-thumbnails#resize
     */
    public function resize(int $width, int $height = 0, array $options = []): ThumbCreator
    {
        $height = $height ?: $width;
        $options += ['aspectRatio' => true, 'upsize' => true];
        Exceptionist::isPositive($width, sprintf('You have to set at least the width for the `%s()` method', __METHOD__));

        $this->arguments[] = [__FUNCTION__, $width, $height, $options];
        $this->callbacks[] = fn(Image $ImageInstance): Image => $ImageInstance
            ->resize($width, $height, function (Constraint $constraint) use ($options): void {
                if ($options['aspectRatio']) {
                    $constraint->aspectRatio();
                }
                if ($options['upsize']) {
                    $constraint->upsize();
                }
            });

        return $this;
    }

    /**
     * Resizes the boundaries of the current image to given width and height. An anchor can be defined to determine from
     *  what point of the image the resizing is going to happen. Set the mode to relative to add or subtract the given
     *  width or height to the actual image dimensions. You can also pass a background color for the emerging area of the image
     * @param int $width Required width
     * @param int $height Required height. If not specified, the width will be used
     * @param array<string, mixed> $options Options for the thumbnail
     * @return self
     * @see https://github.com/mirko-pagliai/php-thumber/wiki/How-to-use-ThumbCreator-and-create-thumbnails#resizecanvas
     */
    public function resizeCanvas(int $width, int $height = 0, array $options = []): ThumbCreator
    {
        $height = $height ?: $width;
        $options += ['anchor' => 'center', 'relative' => false, 'bgcolor' => '#ffffff'];
        Exceptionist::isPositive($width, sprintf('You have to set at least the width for the `%s()` method', __METHOD__));

        $this->arguments[] = [__FUNCTION__, $width, $height, $options];
        $this->callbacks[] = fn(Image $ImageInstance): Image => $ImageInstance
            ->resizeCanvas($width, $height, $options['anchor'], $options['relative'], $options['bgcolor']);

        return $this;
    }

    /**
     * Saves the thumbnail and returns its path
     * @param array<string, mixed> $options Options for saving
     * @return string Thumbnail path
     * @throws \Tools\Exception\NotWritableException
     * @throws \Thumber\Exception\NotReadableImageException
     * @throws \ErrorException
     * @see https://github.com/mirko-pagliai/php-thumber/wiki/How-to-use-ThumbCreator-and-create-thumbnails#save-the-thumbnail
     */
    public function save(array $options = []): string
    {
        Exceptionist::isTrue($this->callbacks, 'No valid method called before the `save()` method');

        $options = $this->getDefaultSaveOptions($options);
        $target = $options['target'];
        $format = $target ? $this->getDefaultSaveOptions([], $target)['format'] : $options['format'];

        if (!$target) {
            $this->arguments[] = [THUMBER_DRIVER, $format, $options['quality']];
            $target = sprintf('%s_%s.%s', md5($this->path), md5(serialize($this->arguments)), $format);
        }

        //Creates the thumbnail, if this does not exist
        $target = $this->Filesystem->makePathAbsolute($target, THUMBER_TARGET);
        if (!file_exists($target)) {
            $ImageInstance = $this->getImageInstance();

            //Calls each callback
            foreach ($this->callbacks as $callback) {
                call_user_func($callback, $ImageInstance);
            }

            $content = $ImageInstance->encode($format, $options['quality']);
            $ImageInstance->destroy();
            try {
                $this->Filesystem->dumpFile($target, (string)$content);
            } catch (IOException $e) {
                throw new NotWritableException(sprintf('Unable to create file `%s`', $target ? $this->Filesystem->rtr($target) : ''));
            }
        }

        //Resets arguments and callbacks
        $this->arguments = $this->callbacks = [];

        return $this->target = $target;
    }
}
