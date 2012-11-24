<?php
/**
 * Class definition file for SLIR (Smart Lencioni Image Resizer)
 *
 * This file is part of SLIR (Smart Lencioni Image Resizer).
 *
 * SLIR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SLIR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SLIR.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright © 2011, Joe Lencioni
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
 * @since 2.0
 * @package SLIR
 */

/**
 * SLIR (Smart Lencioni Image Resizer)
 * Resizes images, intelligently sharpens, crops based on width:height ratios,
 * color fills transparent GIFs and PNGs, and caches variations for optimal
 * performance.
 *
 * I love to hear when my work is being used, so if you decide to use this,
 * feel encouraged to send me an email. I would appreciate it if you would
 * include a link on your site back to Shifting Pixel (either the SLIR page or
 * shiftingpixel.com), but don?t worry about including a big link on each page
 * if you don?t want to?one will do just nicely. Feel free to contact me to
 * discuss any specifics (joe@shiftingpixel.com).
 *
 * REQUIREMENTS:
 *     - PHP 5.1.2+
 *     - GD
 *
 * RECOMMENDED:
 *     - mod_rewrite
 *
 * USAGE:
 * To use, place an img tag with the src pointing to the path of SLIR (typically
 * "/slir/") followed by the parameters, followed by the path to the source
 * image to resize. All parameters follow the pattern of a one-letter code and
 * then the parameter value:
 *     - Maximum width = w
 *     - Maximum height = h
 *     - Crop ratio = c
 *     - Quality = q
 *     - Background fill color = b
 *     - Progressive = p
 *
 * Note: filenames that include special characters must be URL-encoded (e.g.
 * plus sign, +, should be encoded as %2B) in order for SLIR to recognize them
 * properly. This can be accomplished by passing your filenames through PHP's
 * rawurlencode() or urlencode() function.
 *
 * EXAMPLES:
 *
 * Resizing a JPEG to a max width of 100 pixels and a max height of 100 pixels:
 * <code><img src="/slir/w100-h100/path/to/image.jpg" alt="Don't forget your alt
 * text" /></code>
 *
 * Resizing and cropping a JPEG into a square:
 * <code><img src="/slir/w100-h100-c1:1/path/to/image.jpg" alt="Don't forget
 * your alt text" /></code>
 *
 * Resizing a JPEG without interlacing (for use in Flash):
 * <code><img src="/slir/w100-p0/path/to/image.jpg" alt="Don't forget your alt
 * text" /></code>
 *
 * Matting a PNG with #990000:
 * <code><img src="/slir/b900/path/to/image.png" alt="Don't forget your alt
 * text" /></code>
 *
 * Without mod_rewrite (not recommended)
 * <code><img src="/slir/?w=100&amp;h=100&amp;c=1:1&amp;i=/path/to/image.jpg"
 * alt="Don't forget your alt text" /></code>
 *
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 *
 * @uses PEL
 *
 * @todo lock files when writing?
 * @todo Prevent SLIR from calling itself
 * @todo Percentage resizing?
 * @todo Animated GIF resizing?
 * @todo Seam carving?
 * @todo Crop zoom?
 * @todo Crop offsets?
 * @todo Remote image fetching?
 * @todo Alternative support for ImageMagick?
 * @todo Prevent files in cache from being read directly?
 * @todo split directory initialization into a separate
 * install/upgrade script with friendly error messages, an opportunity to give a
 * tip, and a button that tells me they are using it on their site if they like
 * @todo document new code
 * @todo clean up new code
 */
class SLIR
{
  /**
   * @since 2.0
   * @var string
   */
  const VERSION = '2.0b4';

  /**
   * @since 2.0
   * @var string
   */
  const CROP_CLASS_CENTERED = 'centered';

  /**
   * @since 2.0
   * @var string
   */
  const CROP_CLASS_TOP_CENTERED = 'topcentered';

  /**
   * @since 2.0
   * @var string
   */
  const CROP_CLASS_SMART = 'smart';

  /**
   * @var string
   * @since 2.0
   */
  const CONFIG_FILENAME = 'slirconfig.class.php';

  /**
   * Request object
   *
   * @since 2.0
   * @uses SLIRRequest
   * @var object
   */
  public $request;

  /**
   * Source image object
   *
   * @since 2.0
   * @uses SLIRImage
   * @var object
   */
  private $source;

  /**
   * Rendered image object
   *
   * @since 2.0
   * @uses SLIRImage
   * @var object
   */
  private $rendered;

  /**
   * Whether or not SLIR has alerady been initialized
   *
   * @since 2.0
   * @var boolean
   */
  private $isSLIRInitialized = false;

  /**
   * Whether or not the cache has already been initialized
   *
   * @since 2.0
   * @var boolean
   */
  private $isCacheInitialized = false;

  /**
   * Headers that have been sent.
   *
   * This is primarily used for testing.
   *
   * @since 2.0
   * @var array
   */
  private $headers = array();

  /**
   * The magic starts here
   *
   * @since 2.0
   */
  final public function __construct()
  {

  }

  /**
   * Destructor method. Try to clean up memory a little.
   *
   * @return void
   * @since 2.0
   */
  final public function __destruct()
  {
    unset($this->request);
    unset($this->source);
    unset($this->rendered);
  }

  /**
   * Sets up SLIR to be able to process image resizing requests
   *
   * @since 2.0
   * @return void
   */
  public function initialize()
  {
    if (!$this->isSLIRInitialized) {
      // This helps prevent unnecessary warnings (which messes up images)
      // on servers that are set to display E_STRICT errors.
      $this->disableStrictErrorReporting();

      // Prevents ob_start('ob_gzhandler') in auto_prepend files from messing
      // up SLIR's output. However, if SLIR is being run from a command line
      // interface, we need to buffer the output so the command line does not
      // get messed up with garbage output of image data.
      if (!$this->isCLI()) {
        $this->escapeOutputBuffering();
      }

     // $this->getConfig();

      // Set up our exception and error handler after the request cache to
      // help keep everything humming along nicely
      //require_once 'slirexceptionhandler.class.php';

      $this->initializeGarbageCollection();

      $this->isSLIRInitialized = true;
    }
  }

  /**
   * Processes the SLIR request from the parameters passed through the URL
   *
   * @since 2.0
   */
  public function processRequestFromURL()
  {   
    $this->initialize();
       
    // Check the cache based on the request URI
    if ($this->shouldUseRequestCache() && $this->isRequestCached()) {
      return $this->serveRequestCachedImage();
    }
     
    // See if there is anything we actually need to do
    
    if ($this->isSourceImageDesired()) {
        // Закомментировал проверку изменений. Теперь в любом случае файл кешируется.
      //return $this->serveSourceImage();
    }
         
    // Check the cache based on the properties of the rendered image
    if ($this->isRenderedCached()) {
      return $this->serveRenderedCachedImage();
    }

    // Image is not cached in any way, so we need to render the image,
    // cache it, and serve it up to the client
    $this->render();
    $this->serveRenderedImage();
  }

  /**
   * @since 2.0
   * @return SLIRRequest
   */
  private function getRequest()
  {
    if (empty($this->request)) {
      $this->request  = new xSLIRRequest();
      $this->request->initialize();
    }

    return $this->request;
  }

  /**
   * @since 2.0
   * @return SLIRImage
   */
  private function getSource()
  {
    if (empty($this->source)) {
      require_once 'libs/gd/slirgdimage.class.php';
      $this->source = new SLIRGDImage($this->getRequest()->path);

      // If either a max width or max height are not specified or larger than
      // the source image we default to the dimension of the source image so
      // they do not become constraints on our resized image.
      if (!$this->getRequest()->width || $this->getRequest()->width > $this->source->getWidth()) {
        $this->getRequest()->width = $this->source->getWidth();
      }

      if (!$this->getRequest()->height ||  $this->getRequest()->height > $this->source->getHeight()) {
        $this->getRequest()->height = $this->source->getHeight();
      }
    }

    return $this->source;
  }

  /**
   * @since 2.0
   * @return SLIRImage
   */
  public function getRendered()
  {
    if (empty($this->rendered)) {
      require_once 'libs/gd/slirgdimage.class.php';
      $this->rendered = new SLIRGDImage();
      $this->rendered->setOriginalPath($this->getSource()->getPath());

      // Cropping
      /*
      To determine the width and height of the rendered image, the following
      should occur.

      If cropping an image is required, we need to:
       1. Compute the dimensions of the source image after cropping before
        resizing.
       2. Compute the dimensions of the resized image before cropping. One of
        these dimensions may be greater than maxWidth or maxHeight because
        they are based on the dimensions of the final rendered image, which
        will be cropped to fit within the specified maximum dimensions.
       3. Compute the dimensions of the resized image after cropping. These
        must both be less than or equal to maxWidth and maxHeight.
       4. Then when rendering, the image needs to be resized, crop offsets
        need to be computed based on the desired method (smart or centered),
        and the image needs to be cropped to the specified dimensions.

      If cropping an image is not required, we need to compute the dimensions
      of the image without cropping. These must both be less than or equal to
      maxWidth and maxHeight.
      */
      if ($this->isCroppingNeeded()) {
        // Determine the dimensions of the source image after cropping and
        // before resizing

        if ($this->getRequest()->cropRatio['ratio'] > $this->getSource()->getRatio()) {
          // Image is too tall so we will crop the top and bottom
          $this->getSource()->setCropHeight($this->getSource()->getWidth() / $this->getRequest()->cropRatio['ratio']);
          $this->getSource()->setCropWidth($this->getSource()->getWidth());
        } else {
          // Image is too wide so we will crop off the left and right sides
          $this->getSource()->setCropWidth($this->getSource()->getHeight() * $this->getRequest()->cropRatio['ratio']);
          $this->getSource()->setCropHeight($this->getSource()->getHeight());
        }
      }

      if ($this->shouldResizeBasedOnWidth()) {
        $resizeFactor = $this->resizeWidthFactor();
        $this->rendered->setHeight(round($resizeFactor * $this->getSource()->getHeight()));
        $this->rendered->setWidth(round($resizeFactor * $this->getSource()->getWidth()));

        // Determine dimensions after cropping
        if ($this->isCroppingNeeded()) {
          $this->rendered->setCropHeight(round($resizeFactor * $this->getSource()->getCropHeight()));
          $this->rendered->setCropWidth(round($resizeFactor * $this->getSource()->getCropWidth()));
        } // if
      } else if ($this->shouldResizeBasedOnHeight()) {
        $resizeFactor = $this->resizeHeightFactor();
        $this->rendered->setWidth(round($resizeFactor * $this->getSource()->getWidth()));
        $this->rendered->setHeight(round($resizeFactor * $this->getSource()->getHeight()));

        // Determine dimensions after cropping
        if ($this->isCroppingNeeded()) {
          $this->rendered->setCropHeight(round($resizeFactor * $this->getSource()->getCropHeight()));
          $this->rendered->setCropWidth(round($resizeFactor * $this->getSource()->getCropWidth()));
        } // if
      } else if ($this->isCroppingNeeded()) {
        // No resizing is needed but we still need to crop
        $ratio  = ($this->resizeUncroppedWidthFactor() > $this->resizeUncroppedHeightFactor())
          ? $this->resizeUncroppedWidthFactor()
          : $this->resizeUncroppedHeightFactor();

        $this->rendered->setWidth(round($ratio * $this->getSource()->getWidth()));
        $this->rendered->setHeight(round($ratio * $this->getSource()->getHeight()));

        $this->rendered->setCropWidth(round($ratio * $this->getSource()->getCropWidth()));
        $this->rendered->setCropHeight(round($ratio * $this->getSource()->getCropHeight()));
      } // if

      $this->rendered->setSharpeningFactor($this->calculateSharpnessFactor())
        ->setBackground($this->getBackground())
        ->setQuality($this->getQuality())
        ->setProgressive($this->getProgressive())
        ->setMimeType($this->getMimeType())
        ->setCropper($this->getRequest()->cropper);

      // Set up the appropriate image handling parameters based on the original
      // image's mime type
      // @todo some of this code should be moved to the SLIRImage class
      /*
      $this->renderedMime       = $this->getSource()->getMimeType();
      if ($this->getSource()->isJPEG()) {
        $this->rendered->progressive  = ($this->getRequest()->progressive !== null)
          ? $this->getRequest()->progressive : SLIRConfig::$defaultProgressiveJPEG;
        $this->rendered->background   = null;
      } else if ($this->getSource()->isPNG()) {
        $this->rendered->progressive  = false;
      } else if ($this->getSource()->isGIF() || $this->getSource()->isBMP()) {
        // We convert GIFs and BMPs to PNGs
        $this->rendered->mime     = 'image/png';
        $this->rendered->progressive  = false;
      } else {
        throw new RuntimeException("Unable to determine type of source image ({$this->getSource()->mime})");
      } // if

      if ($this->isBackgroundFillOn()) {
        $this->rendered->background = $this->getRequest()->background;
      }
      */
    }

    return $this->rendered;
  }

  /**
   * Checks to see if the request cache should be used
   *
   * @since 2.0
   * @return boolean
   */
  private function shouldUseRequestCache()
  {
    // The request cache can't be used if the request is falling back to the
    // default image path because it will prevent the actual image from being
    // shown if it eventually ends up on the server
    if (SLIRConfig::$enableRequestCache === true && !$this->getRequest()->isUsingDefaultImagePath()) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Disables E_STRICT and E_NOTICE error reporting
   *
   * @since 2.0
   * @return integer
   */
  private function disableStrictErrorReporting()
  {
    return error_reporting(error_reporting() & ~E_STRICT & ~E_NOTICE);
  }

  /**
   * Escapes from output buffering.
   *
   * @since 2.0
   * @return void
   */
  final public function escapeOutputBuffering()
  {
    while ($level = ob_get_level()) {
      ob_end_clean();

      if ($level == ob_get_level()) {
        // On some setups, ob_get_level() will return a 1 instead of a 0 when there are no more buffers
        return;
      }
    }
  }

  /**
   * Determines if the garbage collector should run for this request.
   *
   * @since 2.0
   * @return boolean
   */
  private function garbageCollectionShouldRun()
  {
    if (rand(1, SLIRConfig::$garbageCollectDivisor) <= SLIRConfig::$garbageCollectProbability) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Checks to see if the garbage collector should be initialized, and if it should, initializes it.
   *
   * @since 2.0
   * @return void
   */
  private function initializeGarbageCollection()
  {
    if ($this->garbageCollectionShouldRun()) {
      // Register this as a shutdown function so the additional processing time
      // will not affect the speed of the request
      register_shutdown_function(array($this, 'collectGarbage'));
    }
  }

  /**
   * @return void
   * @since 2.0
   */
  public function collectGarbage()
  {
    // Shut down the connection so the user can go about his or her business
    $this->header('Connection: close');
    ignore_user_abort(true);
    flush();

    require_once 'slirgarbagecollector.class.php';
    $garbageCollector = new SLIRGarbageCollector(array(
      $this->getRequestCacheDir() => false,
      $this->getRenderedCacheDir() => true,
    ));
  }

  /**
   * Includes the configuration file.
   *
   * If the configuration file cannot be included, this will throw an error that will hopefully explain what needs to be done.
   *
   * @since 2.0
   * @return void
   */
  final public function getConfig()
  {
    require_once $this->getConfigPath();
  }

  /**
   * @since 2.0
   * @return string
   */
  final public function getConfigPath()
  {
    if (defined('SLIR_CONFIG_FILENAME')) {
      return SLIR_CONFIG_FILENAME;
    } else {
      return $this->resolveRelativePath('../' . self::CONFIG_FILENAME);
    }
  }

  /**
   * @param string $path
   * @return string
   * @since 2.0
   */
  final public function resolveRelativePath($path)
  {
    $path = __DIR__ . '/' . $path;

    while (strstr($path, '../')) {
      $path = preg_replace('/\w+\/\.\.\//', '', $path);
    }

    return $path;
  }

  /**
   * Renders requested changes to the image
   *
   * @since 2.0
   * @return void
   */
  private function render()
  {
    ini_set('memory_limit', SLIRConfig::$maxMemoryToAllocate . 'M');
    $this->copySourceToRendered();
    $this->getSource()->destroy();
    $this->getRendered()->applyTransformations();
  }

  /**
   * Copies the source image to the rendered image, resizing (resampling) it if resizing is requested
   *
   * @since 2.0
   * @return void
   */
  private function copySourceToRendered()
  {
    // Set up the background. If there is a color fill, it needs to happen
    // before copying the image over.
    $this->getRendered()->background();

    // Resample the original image into the resized canvas we set up earlier
    if ($this->getSource()->getWidth() !== $this->getRendered()->getWidth() || $this->getSource()->getHeight() != $this->getRendered()->getHeight()) {
      $this->getSource()->resample($this->getRendered());
    } else {
      // No resizing is needed, so make a clean copy
      $this->getSource()->copy($this->getRendered());
    } // if
  }

  /**
   * Calculates how much to sharpen the image based on the difference in dimensions of the source image and the rendered image
   *
   * @since 2.0
   * @return integer Sharpness factor
   */
  private function calculateSharpnessFactor()
  {
    return $this->calculateASharpnessFactor($this->getSource()->getArea(), $this->getRendered()->getArea());
  }

  /**
   * Calculates sharpness factor to be used to sharpen an image based on the
   * area of the source image and the area of the destination image
   *
   * @since 2.0
   * @author Ryan Rud
   * @link http://adryrun.com
   *
   * @param integer $sourceArea Area of source image
   * @param integer $destinationArea Area of destination image
   * @return integer Sharpness factor
   */
  private function calculateASharpnessFactor($sourceArea, $destinationArea)
  {
    $final  = sqrt($destinationArea) * (750.0 / sqrt($sourceArea));
    $a      = 52;
    $b      = -0.27810650887573124;
    $c      = .00047337278106508946;

    $result = $a + $b * $final + $c * $final * $final;

    return max(round($result), 0);
  }

  /**
   * Copies IPTC data from the source image to the cached file
   *
   * @since 2.0
   * @param string $cacheFilePath
   * @return boolean
   */
  private function copyIPTC($cacheFilePath)
  {
    $data = '';

    $iptc = $this->getSource()->iptc;

    // Originating program
    $iptc['2#065']  = array('Smart Lencioni Image Resizer');

    // Program version
    $iptc['2#070']  = array(SLIR::VERSION);

    foreach ($iptc as $tag => $iptcData) {
      $tag  = substr($tag, 2);
      $data .= $this->makeIPTCTag(2, $tag, $iptcData[0]);
    }

    // Embed the IPTC data
    return iptcembed($data, $cacheFilePath);
  }

  /**
   * @since 2.0
   * @author Thies C. Arntzen
   */
  private function makeIPTCTag($rec, $data, $value)
  {
    $length = strlen($value);
    $retval = chr(0x1C) . chr($rec) . chr($data);

    if ($length < 0x8000) {
      $retval .= chr($length >> 8) .  chr($length & 0xFF);
    } else {
      $retval .= chr(0x80) .
       chr(0x04) .
       chr(($length >> 24) & 0xFF) .
       chr(($length >> 16) & 0xFF) .
       chr(($length >> 8) & 0xFF) .
       chr($length & 0xFF);
    }

    return $retval . $value;
  }

  /**
   * Checks parameters against the image's attributes and determines whether
   * anything needs to be changed or if we simply need to serve up the source
   * image
   *
   * @since 2.0
   * @return boolean
   * @todo Add check for JPEGs and progressiveness
   */
  private function isSourceImageDesired()
  {
    if ($this->isWidthDifferent() || $this->isHeightDifferent() || $this->isBackgroundFillOn() || $this->isQualityOn() || $this->isCroppingNeeded()) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * Determines if the requested width is different than the width of the source image
   *
   * @since 2.0
   * @return boolean
   */
  private function isWidthDifferent()
  {
    if ($this->getRequest()->width !== null && $this->getRequest()->width < $this->getSource()->getWidth()) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Determines if the requested height is different than the height of the source image
   *
   * @since 2.0
   * @return boolean
   */
  private function isHeightDifferent()
  {
    if ($this->getRequest()->height !== null && $this->getRequest()->height < $this->getSource()->getHeight()) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Determines if a background fill has been requested and if the image is able to have transparency (not for JPEG files)
   *
   * @since 2.0
   * @return boolean
   */
  private function isBackgroundFillOn()
  {
    if ($this->getRequest()->isBackground() && $this->getSource()->isAbleToHaveTransparency()) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Determines if the user included image quality in the request
   *
   * @since 2.0
   * @return boolean
   */
  private function isQualityOn()
  {
    if ($this->getQuality() < 100) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Determines if the image should be cropped based on the requested crop ratio and the width:height ratio of the source image
   *
   * @since 2.0
   * @return boolean
   */
  private function isCroppingNeeded()
  {
    if ($this->getRequest()->isCropping() && $this->getRequest()->cropRatio['ratio'] != $this->getSource()->getRatio()) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Determine the quality to use when rendering the image
   * @return integer
   * @since 2.0
   */
  private function getQuality()
  {
    if ($this->getRequest()->quality !== null) {
      return $this->getRequest()->quality;
    } else {
      return SLIRConfig::$defaultQuality;
    }
  }

  /**
   * Determine whether the rendered image should be progressive or not
   * @return boolean
   * @since 2.0
   */
  private function getProgressive()
  {
    if ($this->getSource()->isJPEG()) {
      return ($this->getRequest()->progressive !== null)
        ? $this->getRequest()->progressive
        : SLIRConfig::$defaultProgressiveJPEG;
    } else {
      return false;
    }
  }

  /**
   * Get the mime type that we want to render as
   * @return string
   * @since 2.0
   */
  private function getMimeType()
  {
    if ($this->getSource()->isGIF() || $this->getSource()->isBMP()) {
      // We convert GIFs and BMPs to PNGs
      return 'image/png';
    } else {
      return $this->getSource()->getMimeType();
    }
  }

  /**
   * @return string
   * @since 2.0
   */
  private function getBackground()
  {
    if ($this->isBackgroundFillOn()) {
      return $this->getRequest()->background;
    } else {
      return false;
    }
  }

  /**
   * Detemrines if the image should be resized based on its width (i.e. the width is the constraining dimension for this request)
   *
   * @since 2.0
   * @return boolean
   */
  private function shouldResizeBasedOnWidth()
  {
    if (floor($this->resizeWidthFactor() * $this->getSource()->getHeight()) <= $this->getRequest()->height) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Detemrines if the image should be resized based on its height (i.e. the height is the constraining dimension for this request)
   * @since 2.0
   * @return boolean
   */
  private function shouldResizeBasedOnHeight()
  {
    if (floor($this->resizeHeightFactor() * $this->getSource()->getWidth()) <= $this->getRequest()->width) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * @since 2.0
   * @return float
   */
  private function resizeWidthFactor()
  {
    if ($this->getSource()->getCropWidth() !== 0) {
      return $this->resizeCroppedWidthFactor();
    } else {
      return $this->resizeUncroppedWidthFactor();
    }
  }

  /**
   * @since 2.0
   * @return float
   */
  private function resizeUncroppedWidthFactor()
  {
    return $this->getRequest()->width / $this->getSource()->getWidth();
  }

  /**
   * @since 2.0
   * @return float
   */
  private function resizeCroppedWidthFactor()
  {
    if ($this->getSource()->getCropWidth() === 0) {
      return false;
    } else {
      return $this->getRequest()->width / $this->getSource()->getCropWidth();
    }
  }

  /**
   * @since 2.0
   * @return float
   */
  private function resizeHeightFactor()
  {
    if ($this->getSource()->getCropHeight() !== 0) {
      return $this->resizeCroppedHeightFactor();
    } else {
      return $this->resizeUncroppedHeightFactor();
    }
  }

  /**
   * @since 2.0
   * @return float
   */
  private function resizeUncroppedHeightFactor()
  {
    return $this->getRequest()->height / $this->getSource()->getHeight();
  }

  /**
   * @since 2.0
   * @return float
   */
  private function resizeCroppedHeightFactor()
  {
    if ($this->getSource()->getCropHeight() === 0) {
      return false;
    } else {
      return $this->getRequest()->height / $this->getSource()->getCropHeight();
    }
  }

  /**
   * Determines if the rendered file is in the rendered cache
   *
   * @since 2.0
   * @return boolean
   */
  public function isRenderedCached()
  {
    return $this->isCached($this->renderedCacheFilePath());
  }

  /**
   * Determines if the request is symlinked to the rendered file
   *
   * @since 2.0
   * @return boolean
   */
  public function isRequestCached()
  {       
    return $this->isCached($this->requestCacheFilePath());
  }

  /**
   * Determines if a given file exists in the cache
   *
   * @since 2.0
   * @param string $cacheFilePath
   * @return boolean
   */
  private function isCached($cacheFilePath)
  {
    if (!file_exists($cacheFilePath)) {
      return false;
    }

    $cacheModified  = filemtime($cacheFilePath);

    if (!$cacheModified) {
      return false;
    }

    $imageModified  = filectime($this->getRequest()->fullPath());

    if ($imageModified >= $cacheModified) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * @since 2.0
   * @return string
   */
  private function getRenderedCacheDir()
  {
    return SLIRConfig::$pathToCacheDir . '/rendered';
  }

  /**
   * @since 2.0
   * @return string
   */
  private function renderedCacheFilePath()
  {
    return $this->getRenderedCacheDir() . $this->renderedCacheFilename();
  }

  /**
   * @since 2.0
   * @return string
   */
  public function renderedCacheFilename()
  {         
    return '/' . $this->getRendered()->getHash();
  }

  /**
   * @since 2.0
   * @return string
   */
  private function getHTTPHost()
  {
    if ($this->isCLI()) {
      return 'CLI';
    } else if (isset($_SERVER['HTTP_HOST'])) {
      return $_SERVER['HTTP_HOST'];
    } else {
      return '';
    }
  }

  /**
   * @since 2.0
   * @return string
   */
  public function requestCacheFilename()
  {
    return '/' . hash('md4', $this->getHTTPHost() . '/' . $this->requestURI() . '/' . SLIRConfig::$defaultCropper);
  }

  /**
   * @since 2.0
   * @return string
   */
  private function requestURI()
  {
    if (SLIRConfig::$forceQueryString === true) {
      return $_SERVER['SCRIPT_NAME'] . '?' . http_build_query($_GET);
    } else {
      return $_SERVER['REQUEST_URI'];
    }
  }

  /**
   * @since 2.0
   * @return string
   */
  public function getRequestCacheDir()
  {
    return SLIRConfig::$pathToCacheDir . '/request';
  }

  /**
   * @since 2.0
   * @return string
   */
  private function requestCacheFilePath()
  {
    return $this->getRequestCacheDir() . $this->requestCacheFilename();
  }

  /**
   * Write an image to the cache
   *
   * @since 2.0
   * @return boolean
   */
  public function cache()
  {
    $this->cacheRendered();

    if ($this->shouldUseRequestCache()) {
      return $this->cacheRequest($this->getRendered()->getData(), true);
    } else {
      return true;
    }
  }

  /**
   * Write an image to the cache based on the properties of the rendered image
   *
   * @since 2.0
   * @return boolean
   */
  private function cacheRendered()
  {
    $this->cacheFile(
        $this->renderedCacheFilePath(),
        $this->getRendered()->getData(),
        true
    );

    return true;
  }

  /**
   * Write an image to the cache based on the request URI
   *
   * @since 2.0
   * @param string $imageData
   * @param boolean $copyEXIF
   * @return string
   */
  public function cacheRequest($imageData, $copyEXIF = true)
  {   
    return $this->cacheFile(
        $this->requestCacheFilePath(),
        $imageData,
        $copyEXIF,
        $this->renderedCacheFilePath()
    );
  }

  /**
   * Write an image to the cache based on the properties of the rendered image
   *
   * @since 2.0
   * @param string $cacheFilePath
   * @param string $imageData
   * @param boolean $copyEXIF
   * @param string $symlinkToPath
   * @return string|boolean
   */
  private function cacheFile($cacheFilePath, $imageData, $copyEXIF = true, $symlinkToPath = null)
  {
    $this->initializeCache();

    // Try to create just a symlink to minimize disk space
    if ($symlinkToPath && function_exists('symlink') && (file_exists($cacheFilePath) || symlink($symlinkToPath, $cacheFilePath))) {
      return true;
    }

    // Create the file
    if (!file_put_contents($cacheFilePath, $imageData)) {
      return false;
    }

    if (SLIRConfig::$copyEXIF == true && $copyEXIF && $this->getSource()->isJPEG()) {
      // Copy IPTC data
      if (isset($this->getSource()->iptc) && !$this->copyIPTC($cacheFilePath)) {
        return false;
      }

      // Copy EXIF data
      $imageData = $this->copyEXIF($cacheFilePath);
    }

    if ($this->getSource()->isJPEG()) {
      // Copy ICC Profile (color profile)
      $imageData = $this->copyICCProfile($cacheFilePath);
    }

    return $imageData;
  }

  /**
   * @since 2.0
   * @return SLIR
   */
  public function uncacheRendered()
  {
    if (file_exists($this->renderedCacheFilePath())) {
      unlink($this->renderedCacheFilePath());
    }
    return $this;
  }

  /**
   * @since 2.0
   * @return SLIR
   */
  public function uncacheRequest()
  {
    if (file_exists($this->requestCacheFilePath())) {
      unlink($this->requestCacheFilePath());
    }
    return $this;
  }

  /**
   * Removes an image from the cache
   *
   * @since 2.0
   * @return SLIR
   */
  public function uncache()
  {
    return $this->uncacheRequest()->uncacheRendered();
  }

  /**
   * Copy the source image's EXIF information to the new file in the cache
   *
   * @since 2.0
   * @uses PEL
   * @param string $cacheFilePath
   * @return mixed string contents of image on success, false on failure
   */
  private function copyEXIF($cacheFilePath)
  {
    // Make sure to suppress strict warning thrown by PEL
    require_once dirname(__FILE__) . '/../pel/src/PelJpeg.php';

    $jpeg   = new PelJpeg($this->getSource()->getFullPath());
    $exif   = $jpeg->getExif();

    if ($exif !== null) {
      $jpeg   = new PelJpeg($cacheFilePath);
      $jpeg->setExif($exif);
      $imageData  = $jpeg->getBytes();

      if (!file_put_contents($cacheFilePath, $imageData)) {
        return false;
      }

      return $imageData;
    } // if

    return file_get_contents($cacheFilePath);
  }

  /**
   * Copy the source images' ICC Profile (color profile) to the new file in the cache
   *
   * @since 2.0
   * @uses PHP JPEG ICC profile manipulator
   * @param string $cacheFilePath
   * @return string contents of image
   *
   * @link http://jpeg-icc.sourceforge.net/
   * @link http://sourceforge.net/projects/jpeg-icc/
   */
  private function copyICCProfile($cacheFilePath)
  {
    require_once dirname(__FILE__) . '/../icc/class.jpeg_icc.php';

    try {
      $o = new JPEG_ICC();
      $o->LoadFromJPEG($this->getSource()->getFullPath());
      $o->SaveToJPEG($cacheFilePath);
    } catch (Exception $e) {
    }

    return file_get_contents($cacheFilePath);
  }

  /**
   * Makes sure the cache directory exists, is readable, and is writable
   *
   * @since 2.0
   * @return boolean
   */
  private function initializeCache()
  {
    if ($this->isCacheInitialized) {
      return true;
    }

    $this->initializeDirectory(SLIRConfig::$pathToCacheDir);
    $this->initializeDirectory(SLIRConfig::$pathToCacheDir . '/rendered', false);
    $this->initializeDirectory(SLIRConfig::$pathToCacheDir . '/request', false);

    $this->isCacheInitialized = true;
    return true;
  }

  /**
   * Determines if SLIR is being run from a command line interface.
   *
   * @since 2.0
   * @return boolean
   */
  private function isCLI()
  {
    return (PHP_SAPI === 'cli');
  }

  /**
   * @since 2.0
   * @param string $header
   * @return SLIR
   */
  private function header($header)
  {
    $this->headers[] = $header;

    if (!$this->isCLI()) {
      header($header);
    }

    return $this;
  }

  /**
   * @since 2.0
   * @return array
   */
  public function getHeaders()
  {
    return $this->headers;
  }

  /**
   * @since 2.0
   * @param string $path Directory to initialize
   * @param boolean $verifyReadWriteability
   * @return boolean
   */
  private function initializeDirectory($path, $verifyReadWriteability = true, $test = false)
  {
    if (!file_exists($path)) {
        /* 
      if (!@mkdir($path, 0755, true)) {
        $this->header('HTTP/1.1 500 Internal Server Error');
        throw new RuntimeException("Directory ($path) does not exist and was unable to be created. Please create the directory.");
      }
      */
    }

    if (!$verifyReadWriteability) {
      return true;
    }

    // Make sure we can read and write the cache directory
    if (!is_readable($path)) {
      $this->header('HTTP/1.1 500 Internal Server Error');
      throw new RuntimeException("Directory ($path) is not readable");
    } else if (!is_writable($path)) {
      $this->header('HTTP/1.1 500 Internal Server Error');
      throw new RuntimeException("Directory ($path) is not writable");
    }

    return true;
  }

  /**
   * Serves the unmodified source image
   *
   * @since 2.0
   * @return void
   */
  private function serveSourceImage()
  {
    return $this->serveFile(
        $this->getSource()->getFullPath(),
        null,
        null,
        null,
        $this->getSource()->getMimeType(),
        'source'
    );
  }

  /**
   * Serves the image from the cache based on the properties of the rendered
   * image
   *
   * @since 2.0
   * @return void
   */
  private function serveRenderedCachedImage()
  {
    return $this->serveCachedImage($this->renderedCacheFilePath(), 'rendered');
  }

  /**
   * Serves the image from the cache based on the request URI
   *
   * @since 2.0
   * @return void
   */
  private function serveRequestCachedImage()
  {
    return $this->serveCachedImage($this->requestCacheFilePath(), 'request');
  }

  /**
   * Serves the image from the cache
   *
   * @since 2.0
   * @param string $cacheFilePath
   * @param string $cacheType Can be 'request' or 'image'
   * @return void
   */
  private function serveCachedImage($cacheFilePath, $cacheType)
  {
    // Serve the image
    $this->serveFile(
        $cacheFilePath,
        null,
        null,
        null,
        null,
        "$cacheType cache"
    );

    // If we are serving from the rendered cache, create a symlink in the
    // request cache to the rendered file
    if ($cacheType != 'request') {
      $this->cacheRequest(file_get_contents($cacheFilePath), false);
    }
  }

  /**
   * Determines the mime type of an image
   *
   * @since 2.0
   * @param string $path
   * @return string
   */
  private function mimeType($path)
  {
    $info = getimagesize($path);
    return $info['mime'];
  }

  /**
   * Serves the rendered image
   *
   * @since 2.0
   * @return void
   */
  private function serveRenderedImage()
  {
    // Cache the image
    $this->cache();

    // Serve the file
    $this->serveFile(
        null,
        $this->getRendered()->getData(),
        gmdate('U'),
        $this->getRendered()->getDatasize(),
        $this->getRendered()->getMimeType(),
        'rendered'
    );

    // Clean up memory
    $this->getRendered()->destroy();
  }

  /**
   * Serves a file
   *
   * @since 2.0
   * @param string $imagePath Path to file to serve
   * @param string $data Data of file to serve
   * @param integer $lastModified Timestamp of when the file was last modified
   * @param string $mimeType
   * @param string $slirHeader
   * @return void
   */
  private function serveFile($imagePath, $data, $lastModified, $length, $mimeType, $slirHeader)
  {
    if ($imagePath !== null) {
      if ($lastModified === null) {
        $lastModified = filemtime($imagePath);
      }
      if ($length === null) {
        $length     = filesize($imagePath);
      }
      if ($mimeType === null) {
        $mimeType   = $this->mimeType($imagePath);
      }
    } else if ($length === null) {
      $length   = strlen($data);
    } // if

    // Serve the headers
    $continue = $this->serveHeaders(
        $this->lastModified($lastModified),
        $mimeType,
        $length,
        $slirHeader
    );

    if (!$continue) {
      return;
    }

    if ($data === null) {
      readfile($imagePath);
    } else {
      echo $data;
    }
  }

  /**
   * Serves headers for file for optimal browser caching
   *
   * @since 2.0
   * @param string $lastModified Time when file was last modified in 'D, d M Y H:i:s' format
   * @param string $mimeType
   * @param integer $fileSize
   * @param string $slirHeader
   * @return boolean true to continue, false to stop
   */
  private function serveHeaders($lastModified, $mimeType, $fileSize, $slirHeader)
  {
    $this->header("Last-Modified: $lastModified");
    $this->header("Content-Type: $mimeType");
    $this->header("Content-Length: $fileSize");

    // Lets us easily know whether the image was rendered from scratch,
    // from the cache, or served directly from the source image
    $this->header("X-Content-SLIR: $slirHeader");

    // Keep in browser cache how long?
    $this->header(sprintf('Expires: %s GMT', gmdate('D, d M Y H:i:s', time() + SLIRConfig::$browserCacheTTL)));

    // Public in the Cache-Control lets proxies know that it is okay to
    // cache this content. If this is being served over HTTPS, there may be
    // sensitive content and therefore should probably not be cached by
    // proxy servers.
    $this->header(sprintf('Cache-Control: max-age=%d, public', SLIRConfig::$browserCacheTTL));

    return $this->doConditionalGet($lastModified);

    // The "Connection: close" header allows us to serve the file and let
    // the browser finish processing the script so we can do extra work
    // without making the user wait. This header must come last or the file
    // size will not properly work for images in the browser's cache
    //$this->header('Connection: close');
  }

  /**
   * Converts a UNIX timestamp into the format needed for the Last-Modified
   * header
   *
   * @since 2.0
   * @param integer $timestamp
   * @return string
   */
  private function lastModified($timestamp)
  {
    return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
  }

  /**
   * Checks the to see if the file is different than the browser's cache
   *
   * @since 2.0
   * @param string $lastModified
   * @return boolean true to continue, false to stop
   */
  private function doConditionalGet($lastModified)
  {
    $ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ?
      stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) :
      false;

    if (!$ifModifiedSince || $ifModifiedSince <= $lastModified) {
      return true;
    }

    // Nothing has changed since their last request - serve a 304 and exit
    $this->header('HTTP/1.1 304 Not Modified');

    return false;
  }

} // class SLIR

// old pond
// a frog jumps
// the sound of water

// —Matsuo Basho


/**
 * Configuration file for SLIR (Smart Lencioni Image Resizer)
 *
 * This file is part of SLIR (Smart Lencioni Image Resizer).
 *
 * SLIR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SLIR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SLIR.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright © 2011, Joe Lencioni
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
 * @since 2.0
 * @package SLIR
 */

/**
 * SLIR Config Class
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 */
class SLIRConfigDefaults
{
  /**
   * Path to default the source image to if the requested image cannot be found.
   *
   * This should match the style of path you would normally pass to SLIR in the URL (not the full path on the filesystem).
   *
   * For example, if your website was http://mysite.com and your document root was /var/www/, and your default image was at http://mysite.com/images/default.png, you would set $defaultImagePath = '/images/default.png';
   *
   * If null, SLIR will throw an exception if the requested image cannot be found.
   *
   * @since 2.0
   * @var string
   */
  public static $defaultImagePath = null;

  /**
   * Default quality setting to use if quality is not specified in the request. Ranges from 0 (worst quality, smaller file) to 100 (best quality, largest filesize).
   *
   * @since 2.0
   * @var integer
   */
  public static $defaultQuality = 80;

  /**
   * Default setting for whether JPEGs should be progressive JPEGs (interlaced) or not.
   *
   * @since 2.0
   * @var boolean
   */
  public static $defaultProgressiveJPEG = true;

  /**
   * How long (in seconds) the web browser should use its cached copy of the image
   * before checking with the server for a new version
   *
   * @since 2.0
   * @var integer
   */
  public static $browserCacheTTL  = 604800; // 7 days = 7 * 24 * 60 * 60

  /**
   * If true, enables the faster, symlink-based request cache as a first-line cache. If false, the request cache is disabled.
   *
   * The request cache seems to have issues on some Windows servers.
   *
   * @since 2.0
   * @var boolean
   */
  public static $enableRequestCache = true;

  /**
   * How much memory (in megabytes) SLIR is allowed to allocate for memory-intensive processes such as rendering and cropping.
   *
   * @since 2.0
   * @var integer
   */
  public static $maxMemoryToAllocate  = 100;

  /**
   * Default crop mode setting to use if crop mode is not specified in the request.
   *
   * Possible values are:
   * SLIR::CROP_CLASS_CENTERED
   * SLIR::CROP_CLASS_TOP_CENTERED
   * SLIR::CROP_CLASS_SMART
   *
   * @since 2.0
   * @var string
   */
  public static $defaultCropper = SLIR::CROP_CLASS_CENTERED;

  /**
   * If true, SLIR will generate and output images from error messages. If false, error messages will be plaintext.
   *
   * @since 2.0
   * @var boolean
   */
  public static $enableErrorImages  = true;

  /**
   * Absolute path to the web root (location of files when visiting http://example.com/) (no trailing slash).
   *
   * For example, if the files for your website are located in /var/www/ on your server, this should be '/var/www'.
   *
   * By default, this is dyanmically determined, so it is set in the init() function and hopefully will not need to be overwritten. However, if SLIR isn't working correctly, it might not be determining your document root correctly and you might need to set this manually in your configuration file.
   *
   * @since 2.0
   * @var string
   */
  public static $documentRoot = null;

  /**
   * Absolute path to SLIR (no trailing slash) from the root directory on your server's filesystem.
   *
   * For example, if the files on your website are in /var/www/ and slir is accessible at http://example.com/slir/, then the value of this setting should be '/var/www/slir'.
   *
   * By default, this is dyanmically determined, so it is set in the init() function and hopefully will not need to be overwritten. However, if SLIR isn't working correctly, it might not be determining the path to SLIR correctly and you might need to set this manually in your configuration file.
   *
   * @since 2.0
   * @var string
   */
  public static $pathToSLIR = null;

  /**
   * Absolute path to cache directory (no trailing slash). This directory must be world-readable, writable by the web server. Ideally, this directory should be located outside of the web tree for security reasons.
   *
   * By default, this is dynamically determined in the init() function and it defaults to /path/to/slir/cache (or $pathToSlir . '/cache') which is the cache directory inside the directory SLIR is located.
   *
   * @var string
   */
  public static $pathToCacheDir = null;

  /**
   * Path to the error log file. Needs to be writable by the web server. Ideally, this should be located outside of the web tree.
   *
   * If not specified, defaults to 'slir-error-log' in the directory SLIR is located.
   *
   * @since 2.0
   * @var string
   */
  public static $pathToErrorLog = null;

  /**
   * If true, forces SLIR to always use the query string for parameters instead of mod_rewrite.
   *
   * @since 2.0
   * @var boolean
   */
  public static $forceQueryString = false;

  /**
   * In conjunction with $garbageCollectDivisor is used to manage probability that the garbage collection routine is started.
   *
   * @since 2.0
   * @var integer
   */
  public static $garbageCollectProbability  = 1;

  /**
   * Coupled with $garbageCollectProbability defines the probability that the garbage collection process is started on every request.
   *
   * The probability is calculated by using $garbageCollectProbability/$garbageCollectDivisor, e.g. 1/100 means there is a 1% chance that the garbage collection process starts on each request.
   *
   * @since 2.0
   * @var integer
   */
  public static $garbageCollectDivisor  = 200;

  /**
   * Specifies the number of seconds after which data will be seen as 'garbage' and potentially cleaned up (deleted from the cache).
   *
   * @since 2.0
   * @var integer
   */
  public static $garbageCollectFileCacheMaxLifetime = 604800; // 7 days = 7 * 24 * 60 * 60

  /**
   * If true, SLIR will copy EXIF information should from the source image to the rendered image.
   *
   * This can be particularly useful (necessary?) if you use an embedded color profile.
   *
   * @since 2.0
   * @var boolean
   */
  public static $copyEXIF = false;

  /**
   * Initialize variables that require some dynamic processing.
   *
   * @since 2.0
   * @return void
   */
  public static function init()
  {
    if (!defined('__DIR__')) {
      define('__DIR__', dirname(__FILE__));
    }

    if (self::$documentRoot === null) {
      self::$documentRoot = rtrim(realpath(preg_replace('`' . preg_quote($_SERVER['PHP_SELF']) . '$`', '', $_SERVER['SCRIPT_FILENAME'])), '/');
    }

    if (self::$pathToSLIR === null) {
      self::$pathToSLIR = realpath(__DIR__ . '/../');
    }

    if (self::$pathToCacheDir === null) {
      self::$pathToCacheDir = self::$pathToSLIR . '/cache/imagecache';
    }

    if (self::$pathToErrorLog === null) {
      self::$pathToErrorLog = self::$pathToSLIR . '/slir-error-log';
    }
  }

}



/**
 * Class definition file for SLIRExceptionHandler
 *
 * This file is part of SLIR (Smart Lencioni Image Resizer).
 *
 * SLIR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SLIR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SLIR.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright © 2011, Joe Lencioni
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
 * @since 2.0
 * @package SLIR
 */

/**
 * Exception and error handler
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 */
class SLIRExceptionHandler
{
  /**
   * Max number of characters to wrap error message at
   *
   * @since 2.0
   * @var integer
   */
  const WRAP_AT   = 65;

  /**
   * Text size to use in imagestring(). Possible values are 1, 2, 3, 4, or 5
   *
   * @since 2.0
   * @var integer
   */
  const TEXT_SIZE   = 4;

  /**
   * Height of one line of text, in pixels
   *
   * @since 2.0
   * @var integer
   */
  const LINE_HEIGHT = 16;

  /**
   * Width of one character of text, in pixels
   *
   * @since 2.0
   * @var integer
   */
  const CHAR_WIDTH  = 8;

  /**
   * Logs the error to a file
   *
   * @since 2.0
   * @param Exception $e
   * @return boolean
   */
  private static function log(Exception $e)
  {
    $userAgent  = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $referrer   = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
    $request    = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] : '';

    $message = vsprintf("\n[%s] [%s %s] %s\n\nREFERRER: %s\n\nREQUEST: %s\n\n%s", array(
        @gmdate('D M d H:i:s Y'),
        $_SERVER['REMOTE_ADDR'],
        $userAgent,
        $e->getMessage(),
        $referrer,
        $request,
        $e->getTraceAsString(),
    ));

    return @error_log($message, 3, SLIRConfig::$pathToErrorLog);
  }

  /**
   * Create and output an image with an error message
   *
   * @since 2.0
   * @param Exception $e
   */
  private static function errorImage(Exception $e)
  {
    $text = wordwrap($e->getMessage(), self::WRAP_AT);
    $text = explode("\n", $text);
    
    // determine width
    $characters = 0;
    foreach ($text as $line) {
      if (($temp = strlen($line)) > $characters) {
        $characters = $temp;
      }
    } // foreach

    // set up the image
    $image  = imagecreatetruecolor(
        $characters * self::CHAR_WIDTH,
        count($text) * self::LINE_HEIGHT
    );
    $white  = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);

    // set text color
    $textColor  = imagecolorallocate($image, 200, 0, 0); // red

    // write the text to the image
    $i  = 0;
    foreach ($text as $line) {
      imagestring(
          $image,
          self::TEXT_SIZE,
          0,
          $i * self::LINE_HEIGHT,
          $line,
          $textColor
      );
      ++$i;
    }

    // output the image
    header('Content-type: image/png');
    imagepng($image);

    // clean up for memory
    imagedestroy($image);
  }

  /**
   * Outputs the error as plain text
   *
   * @since 2.0
   * @param Exception $e
   * @return void
   */
  private static function errorText(Exception $e)
  {
    echo nl2br($e->getMessage() . ' in ' . $e->getFile() . ' on ' . $e->getLine()) . "\n";
  }

  /**
   * Exception handler
   *
   * @since 2.0
   * @param Exception $e
   * @return void
   */
  public static function handleException(Exception $e)
  {
    if (SLIRConfig::$enableErrorImages === true) {
      self::errorImage($e);
    } else {
      self::errorText($e);
    }

    self::log($e);
  }

  /**
   * Error handler
   *
   * Converts all errors into exceptions so they can be handled with the SLIR exception handler
   *
   * @since 2.0
   * @param integer $severity Level of the error raised
   * @param string $message Error message
   * @param string $filename Filename that the error was raised in
   * @param integer $lineno Line number the error was raised at,
   * @param array $context Points to the active symbol table at the point the error occurred
   */
  public static function handleError($severity, $message, $filename = null, $lineno = null, $context = array())
  {
    if (!(error_reporting() & $severity)) {
      // This error code is not included in error_reporting
      return;
    }

    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}

set_error_handler(array('SLIRExceptionHandler', 'handleError'));
set_exception_handler(array('SLIRExceptionHandler', 'handleException'));


/**
 * Class definition file for SLIRGarbageCollector
 *
 * This file is part of SLIR (Smart Lencioni Image Resizer).
 *
 * SLIR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SLIR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SLIR.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright © 2011, Joe Lencioni
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
 * @since 2.0
 * @package SLIR
 */

/**
 * SLIR garbage collector class
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 */
class SLIRGarbageCollector
{

  /**
   * Setting for the garbage collector to sleep for a second after looking at this many files
   *
   * @since 2.0
   * @var integer
   */
  const BREATHE_EVERY = 5000;

  /**
   * Garbage collector
   *
   * Clears out old files from the cache
   *
   * @since 2.0
   * @param array $directories
   * @return void
   */
  public function __construct(array $directories)
  {
    // This code needs to be in a try/catch block to prevent the epically unhelpful
    // "PHP Fatal error:  Exception thrown without a stack frame in Unknown on line
    // 0" from showing up in the error log.
    try {
      if ($this->isRunning()) {
        return;
      }

      $this->start();
      foreach ($directories as $directory => $useAccessedTime) {
        $this->deleteStaleFilesFromDirectory($directory, $useAccessedTime);
      }
      $this->finish();
    } catch (Exception $e) {
      error_log(sprintf("\n[%s] %s thrown within the SLIR garbage collector. Message: %s in %s on line %d", @gmdate('D M d H:i:s Y'), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()), 3, SLIRConfig::$pathToErrorLog);
      error_log("\nException trace stack: " . print_r($e->getTrace(), true), 3, SLIRConfig::$pathToErrorLog);
      $this->finish(false);
    }
  }

  /**
   * Deletes stale files from a directory.
   *
   * Used by the garbage collector to keep the cache directories from overflowing.
   *
   * @param string $path Directory to delete stale files from
   */
  private function deleteStaleFilesFromDirectory($path, $useAccessedTime = true)
  {
    $now  = time();
    $dir  = new DirectoryIterator($path);

    if ($useAccessedTime === true) {
      $function = 'getATime';
    } else {
      $function = 'getCTime';
    }

    foreach ($dir as $file) {
      // Every x files, stop for a second to help let other things on the server happen
      if ($file->key() % self::BREATHE_EVERY == 0) {
        sleep(1);
      }

      // If the file is a link and not readable, the file it was pointing at has probably
      // been deleted, so we need to delete the link.
      // Otherwise, if the file is older than the max lifetime specified in the config, it is
      // stale and should be deleted.
      if (!$file->isDot() && (($file->isLink() && !$file->isReadable()) || ($now - $file->$function()) > SLIRConfig::$garbageCollectFileCacheMaxLifetime)) {
        unlink($file->getPathName());
      }
    }

    unset($dir);
  }

  /**
   * Checks to see if the garbage collector is currently running.
   *
   * @since 2.0
   * @return boolean
   */
  private function isRunning()
  {
    if (file_exists(SLIRConfig::$pathToCacheDir . '/garbageCollector.tmp') && filemtime(SLIRConfig::$pathToCacheDir . '/garbageCollector.tmp') > time() - 86400) {
      // If the file is more than 1 day old, something probably went wrong and we should run again anyway
      return true;
    } else {
      return false;
    }
  }

  /**
   * Writes a file to the cache to use as a signal that the garbage collector is currently running.
   *
   * @since 2.0
   * @return void
   */
  private function start()
  {
    error_log(sprintf("\n[%s] Garbage collection started", @gmdate('D M d H:i:s Y')), 3, SLIRConfig::$pathToErrorLog);

    // Create the file that tells SLIR that the garbage collector is currently running and doesn't need to run again right now.
    touch(SLIRConfig::$pathToCacheDir . '/garbageCollector.tmp');
  }

  /**
   * Removes the file that signifies that the garbage collector is currently running.
   *
   * @since 2.0
   * @param boolean $successful
   * @return void
   */
  private function finish($successful = true)
  {
    // Delete the file that tells SLIR that the garbage collector is running
    unlink(SLIRConfig::$pathToCacheDir . '/garbageCollector.tmp');

    if ($successful) {
      error_log(sprintf("\n[%s] Garbage collection completed", @gmdate('D M d H:i:s Y')), 3, SLIRConfig::$pathToErrorLog);
    }
  }
}



/**
 * Class definition file for SLIRRequest
 *
 * This file is part of SLIR (Smart Lencioni Image Resizer).
 *
 * SLIR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SLIR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SLIR.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright © 2011, Joe Lencioni
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
 * @since 2.0
 * @package SLIR
 */

/**
 * SLIR request class
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 */
class SLIRRequest
{

  const CROP_RATIO_DELIMITERS = ':.x';

  /**
   * Path to image
   *
   * @since 2.0
   * @var string
   */
  private $path;

  /**
   * Maximum width for resized image, in pixels
   *
   * @since 2.0
   * @var integer
   */
  private $width;

  /**
   * Maximum height for resized image, in pixels
   *
   * @since 2.0
   * @var integer
   */
  private $height;

  /**
   * Ratio of width:height to crop image to.
   *
   * For example, if a square shape is desired, the crop ratio should be "1:1"
   * or if a long rectangle is desired, the crop ratio could be "4:1". Stored
   * as an associative array with keys being 'width' and 'height'.
   *
   * @since 2.0
   * @var array
   */
  private $cropRatio;

  /**
   * Name of the cropper to use, e.g. 'centered' or 'smart'
   *
   * @since 2.0
   * @var string
   */
  private $cropper;

  /**
   * Quality of rendered image
   *
   * @since 2.0
   * @var integer
   */
  private $quality;

  /**
   * Whether or not progressive JPEG output is turned on
   * @var boolean
   * @since 2.0
   */
  private $progressive;

  /**
   * Color to fill background of transparent PNGs and GIFs
   * @var string
   * @since 2.0
   */
  private $background;

  /**
   * @since 2.0
   * @var boolean
   */
  private $isUsingDefaultImagePath  = false;

  /**
   * @since 2.0
   */
  final public function __construct()
  {
  }

  /**
   * @since 2.0
   */
  final public function initialize()
  {
    $params = $this->getParameters();

    // Set image path first
    if (isset($params['i']) && $params['i'] != '' && $params['i'] != '/') {
      $this->__set('i', $params['i']);
      unset($params['i']);
    } else if (SLIRConfig::$defaultImagePath !== null) {
      $this->__set('i', SLIRConfig::$defaultImagePath);
    } else {
      throw new RuntimeException('Source image was not specified.');
    } // if

    // Set the rest of the parameters
    foreach ($params as $name => $value) {
      $this->__set($name, $value);
    } // foreach
    
  }

  /**
   * Destructor method. Try to clean up memory.
   *
   * @return void
   * @since 2.0
   */
  final public function __destruct()
  {
    unset($this->path);
    unset($this->width);
    unset($this->height);
    unset($this->cropRatio);
    unset($this->cropper);
    unset($this->quality);
    unset($this->progressive);
    unset($this->background);
    unset($this->isUsingDefaultImagePath);
  }

  /**
   * @since 2.0
   * @return void
   */
  final public function __set($name, $value)
  {
    switch ($name) {
      case 'i':
      case 'image':
      case 'imagePath':
      case 'path':
        $this->setPath($value);
          break;

      case 'w':
      case 'width':
        $this->setWidth($value);
          break;

      case 'h':
      case 'height':
        $this->setHeight($value);
          break;

      case 'q':
      case 'quality':
        $this->setQuality($value);
          break;

      case 'p':
      case 'progressive':
        $this->setProgressive($value);
          break;

      case 'b';
      case 'background':
      case 'backgroundFillColor':
        $this->setBackgroundFillColor($value);
          break;

      case 'c':
      case 'cropRatio':
        $this->setCropRatio($value);
          break;
    } // switch
  }

  /**
   * @since 2.0
   * @return mixed
   */
  final public function __get($name)
  {
    return $this->$name;
  }

  /**
   * @since 2.0
   * @return void
   */
  private function setWidth($value)
  {
    $this->width  = (int) $value;
    if ($this->width < 1) {
      throw new RuntimeException('Width must be greater than 0: ' . $this->width);
    }
  }

  /**
   * @since 2.0
   * @return void
   */
  private function setHeight($value)
  {
    $this->height = (int) $value;
    if ($this->height < 1) {
      throw new RuntimeException('Height must be greater than 0: ' . $this->height);
    }
  }

  /**
   * @since 2.0
   * @return void
   */
  private function setQuality($value)
  {
    $this->quality  = (int) $value;
    if ($this->quality < 0 || $this->quality > 100) {
      throw new RuntimeException('Quality must be between 0 and 100: ' . $this->quality);
    }
  }

  /**
   * @param string $value
   * @return void
   */
  private function setProgressive($value)
  {
    $this->progressive  = (bool) $value;
  }

  /**
   * @param string $value
   * @return void
   */
  private function setBackgroundFillColor($value)
  {
    $this->background = preg_replace('/[^0-9a-fA-F]/', '', $value);

    if (strlen($this->background) == 3) {
      $this->background = $this->background[0]
        .$this->background[0]
        .$this->background[1]
        .$this->background[1]
        .$this->background[2]
        .$this->background[2];
    } else if (strlen($this->background) != 6) {
      throw new RuntimeException('Background fill color must be in hexadecimal format, longhand or shorthand: ' . $this->background);
    } // if
  }

  /**
   * @param string $value
   * @return void
   */
  private function setCropRatio($value)
  {
    $delimiters = preg_quote(self::CROP_RATIO_DELIMITERS);
    $ratio      = preg_split("/[$delimiters]/", (string) urldecode($value));
    if (count($ratio) >= 2) {
      if ((float) $ratio[0] == 0 || (float) $ratio[1] == 0) {
        throw new RuntimeException('Crop ratio must not contain a zero: ' . (string) $value);
      }

      $this->cropRatio  = array(
        'width'   => (float) $ratio[0],
        'height'  => (float) $ratio[1],
        'ratio'   => (float) $ratio[0] / (float) $ratio[1]
      );

      // If there was a third part, that is the cropper being specified
      if (count($ratio) >= 3) {
        $this->cropper  = (string) $ratio[2];
      }
    } else {
      throw new RuntimeException('Crop ratio must be in [width]x[height] format (e.g. 2x1): ' . (string) $value);
    } // if
  }

  /**
   * Determines the parameters to use for resizing
   *
   * @since 2.0
   * @return array
   */
  public function getParameters()
  {      
    if (!$this->isUsingQueryString()) {
      // Using the mod_rewrite version
      return $this->getParametersFromURL();
    } else {
      // Using the query string version
      return $_GET;
    }
  }

  /**
   * Gets parameters from the URL
   *
   * This is used for requests that are using the mod_rewrite syntax
   *
   * @since 2.0
   * @return array
   */
  public function getParametersFromURL()
  {
    $params = array();
       
    // The parameters should be the first set of characters after the SLIR path
    $request    = preg_replace('`.*?/' . preg_quote(basename(SLIRConfig::$pathToSLIR)) . '/`', '', (string) $_SERVER['REQUEST_URI'], 1);
    $paramString  = strtok($request, '/');

    if ($paramString === false || $paramString === $request) {
      throw new RuntimeException('Not enough parameters were given.

Available parameters:
 w = Maximum width
 h = Maximum height
 c = Crop ratio (width.height(.cropper?))
 q = Quality (0-100)
 b = Background fill color (RRGGBB or RGB)
 p = Progressive (0 or 1)

Example usage:
/slir/w300-h300-c1.1/path/to/image.jpg');

    }

    // The image path should start right after the parameters
    $params['i']  = substr($request, strlen($paramString) + 1); // +1 for the slash

    // The parameters are separated by hyphens
    $rawParam   = strtok($paramString, '-');
    while ($rawParam !== false) {
      if (strlen($rawParam) > 1) {
        // The name of each parameter should be the first character of the parameter string and the value of each parameter should be the remaining characters of the parameter string
        $params[$rawParam[0]] = substr($rawParam, 1);
      }

      $rawParam = strtok('-');
    }

    return $params;
  }

  /**
   * Determines if the request is using the mod_rewrite version or the query
   * string version
   *
   * @since 2.0
   * @return boolean
   */
  public function isUsingQueryString()
  {
    if (SLIRConfig::$forceQueryString === true) {
      return true;
    } else if (!empty($_SERVER['QUERY_STRING']) && count(array_intersect(array('i', 'w', 'h', 'q', 'c', 'b', 'p'), array_keys($_GET)))) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Checks if the default image path set in the config is being used for this request
   *
   * @since 2.0
   * @return boolean
   */
  public function isUsingDefaultImagePath()
  {
    return $this->isUsingDefaultImagePath;
  }

  /**
   * @since 2.0
   * @param string $path
   */
  private function setPath($path)
  {
    $this->path = $this->localizePath((string) urldecode($path));

    if (!$this->isPathSecure()) {
      // Make sure the image path is secure
      throw new RuntimeException('Image path may not contain ":", "..", "<", or ">"');
    } else if (!$this->pathExists()) {
      // Make sure the image file exists
      if (SLIRConfig::$defaultImagePath !== null && !$this->isUsingDefaultImagePath()) {
        $this->isUsingDefaultImagePath  = true;
        return $this->setPath(SLIRConfig::$defaultImagePath);
      } else {
        throw new RuntimeException('Image does not exist: ' . $this->fullPath());
      }
    }
  }

  /**
   * Strips the domain and query string from the path if either is there
   * @since 2.0
   * @return string
   */
  private function localizePath($path)
  {
    return '/' . trim($this->stripQueryString($this->stripProtocolAndDomain($path)), '/');
  }

  /**
   * Strips the protocol and domain from the path if it is there
   * @since 2.0
   * @return string
   */
  private function stripProtocolAndDomain($path)
  {
    return preg_replace('/^[^:]+:\/\/[^\/]+/i', '', $path);
  }

  /**
   * Strips the query string from the path if it is there
   * @since 2.0
   * @return string
   */
  private function stripQueryString($path)
  {
    return preg_replace('/\?.*+/', '', $path);
  }

  /**
   * Checks to see if the path is secure
   *
   * For security, directories may not contain ':' and images may not contain
   * '..', '<', or '>'.
   *
   * @since 2.0
   * @return boolean
   */
  private function isPathSecure()
  {      
    if (strpos(dirname($this->path), ':') || preg_match('/(?:\.\.|<|>)/', $this->path)) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * Determines if the path exists
   *
   * @since 2.0
   * @return boolean
   */
  private function pathExists()
  {
    return is_file($this->fullPath());
  }

  /**
   * @return string
   * @since 2.0
   */
  final public function fullPath()
  {
    return SLIRConfig::$documentRoot . $this->path;
  }

  /**
   * @since 2.0
   * @return boolean
   */
  final public function isBackground()
  {
    if ($this->background !== null) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * @since 2.0
   * @return boolean
   */
  final public function isQuality()
  {
    if ($this->quality !== null) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * @since 2.0
   * @return boolean
   */
  final public function isCropping()
  {
    if ($this->cropRatio['width'] !== null && $this->cropRatio['height'] !== null) {
      return true;
    } else {
      return false;
    }
  }

}