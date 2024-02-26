<?php
/**
 * Generates CSS Sprites
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;

use Legacy\Jot\Exceptions\JotFormException;
use Legacy\Jot\JotErrors;

class CssSprite
{
    # Groups array that will hold the properties of the images.
    #
    # groups = array (
    # groupname=> array(
    #      "name"    => folder name same as group name (String)
    #      "path"    => path of the group folder (String)
    #      "x" => array(
    #          "images" => array(imageFileName  => array (
    #              "name"  => name of the image
    #              "path"  => path of the image
    #              "height"=> height of the image
    #              "width" => width of the image
    #              "resource"  => source fo the image
    #          ))
    #          "widths" => the widths array of the images
    #          "heights" => the widths array of the images
    #          "cssCode" => the css code of the group
    #      )
    #      "y" => array(
    #          "images" => array(imageFileName  => array (
    #              "name"  => name of the image
    #              "path"  => path of the image
    #              "height"=> height of the image
    #              "width" => width of the image
    #              "resource" => source fo the image
    #          ))
    #          "widths" =>the widths array of the images
    #          "heights" =>the widths array of the images
    #          "cssCode" => the css code of the group
    #      )
    #      "none" => array(
    #          "images" => array(imageFileName  => array (
    #              "name"  => name of the image
    #              "path"  => path of the image
    #              "height"=> height of the image
    #              "width" => width of the image
    #              "resource" => source fo the image
    #          ))
    #          "widths" =>the widths array of the images
    #          "heights" =>the widths array of the images
    #          "cssCode" => the css code of the group
    #      )
    #  )
    # )
    private static $groups = array();

    const inputFolder = IMAGE_FOLDER;   # Groups folder that will be converted.
    const outputFolder = SPRITE_FOLDER; # Folder that the outputs will saved
    const xRepeatFolderName = "x-repeat";  # x repeat folder name.
    const yRepeatFolderName = "y-repeat";  # y repeat folder name.

    /**
     * Main function of the class.
     * @return
     */
    public static function convertToCssSprite()
    {
        # Control if GD library is installed.
        if (!function_exists("gd_info")) {
            throw new JotFormException(JotErrors::$SPRITE_GD_NOT_FOUND);
        }
        # Control the inputFolder
        if (!is_dir(self::inputFolder)) {
            throw new JotFormException(JotErrors::get('SPRITE_FOLDER_NOT_FOUND', self::inputFolder));
        }
        # Control output folder. If does not exists create it.
        if (!is_dir(self::outputFolder)) {
            if (!mkdir(self::outputFolder)) {
                throw new JotFormException(JotErrors::get('SPRITE_FOLDER_NOT_CREATED', self::outputFolder));
            }
        }
        # Find and set the groups
        self::setGroups();
        # Set the images
        self::initializeImages();
        # Optimize the result images
        self::optimzePNG();
    }

    /*
     * Optimize PNG files.
     */
    private function optimzePNG()
    {
        # Optimize and copy each file in temp_folder
        foreach (self::$groups as $groupName => $groupProperties) {
            $tempImage = self::outputFolder . DIRECTORY_SEPARATOR . "temp_images" . DIRECTORY_SEPARATOR . $groupName;
            $realImage = self::outputFolder . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . $groupName;
            exec(Utils::findCommand("pngcrush") . " {$tempImage}.png {$realImage}.png");
            exec(Utils::findCommand("pngcrush") . " {$tempImage}_x.png {$realImage}_x.png");
            exec(Utils::findCommand("pngcrush") . " {$tempImage}_y.png {$realImage}_y.png");
        }
    }

    /**
     * Sets the groups inside the input image folder
     */
    private function setGroups()
    {
        $d = dir(self::inputFolder);
        while (false !== ($entry = $d->read())) {
            $groupFolderPath = self::inputFolder . DIRECTORY_SEPARATOR . $entry;
            # Add to the groups if group is folder and not hidden.
            if (is_dir($groupFolderPath) && !preg_match('/^\./', $entry)) {
                self::$groups[$entry] = array("path" => $groupFolderPath, "name" => $entry);
            }
        }
    }

    private function initializeImages()
    {
        # For each group create the image folder and css file.
        foreach (self::$groups as &$properties) {

            # Get the path of x repeat images
            $xRepeatFolder = $properties['path'] . DIRECTORY_SEPARATOR . self::xRepeatFolderName;
            if (is_dir($xRepeatFolder)) {
                if (self::setImageProperties($properties, $xRepeatFolder, "x") === false) {
                    continue;
                }
                self::createXRepeatSpriteImageAndCSS($properties);
            }

            # Get the path of y repeat images
            $yRepeatFolder = $properties['path'] . DIRECTORY_SEPARATOR . self::yRepeatFolderName;
            if (is_dir($yRepeatFolder)) {
                if (self::setImageProperties($properties, $yRepeatFolder, "y") === false) {
                    continue;
                }
                self::createYRepeatSpriteImageAndCSS($properties);
            }

            # Get the path of none repeat images
            $noneRepeatFolder = $properties['path'];
            if (is_dir($noneRepeatFolder)) {
                if (self::setImageProperties($properties, $noneRepeatFolder, "none") === false) {
                    continue;
                }
                self::createNoneRepeatSpriteImageAndCSS($properties);
            }
            # Create the css file
            if (!@file_put_contents(self::outputFolder . DIRECTORY_SEPARATOR . $properties["name"] . ".css", $properties["x"]["cssCode"] . $properties["y"]["cssCode"] . $properties["none"]["cssCode"])) {
                throw new JotFormException(JotErrors::$SPRITE_CANNOT_WRITE);
            }
        }
    }

    private function createEmptyImage($width, $height)
    {
        $image = imagecreatetruecolor($width, $height);
        # Restore transparency blending
        imagesavealpha($image, true);
        # Create a new transparent color for image
        $color = imagecolorallocatealpha($image, 0, 0, 0, 127);
        # Completely fill the background of the new image with allocated color.
        imagefill($image, 0, 0, $color);
        imagealphablending($image, true);
        return $image;
    }

    private function copyImageWithTrueColors($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h)
    {
        imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
    }

    private function createNoneRepeatSpriteImageAndCSS(&$properties)
    {
        $width = array_sum($properties["none"]["widths"]);
        $height = max($properties["none"]["heights"]);

        $noneRepeatImage = self::createEmptyImage($width, $height);

        # Create the CSS Code (the background images)
        if (count($properties["none"]["images"]) > 0) {

            $cssSelectorNames = array();

            foreach ($properties["none"]["images"] as $imageName => $imageProperties) {
                $cssSelectorNames[] = $properties['name'] . "-" . $imageName;
            }

            $properties["none"]["cssCode"] .= "." . implode(", .", $cssSelectorNames)
                . "{background: transparent url(images/" . $properties["name"] . ".png?" . VERSION . ") no-repeat scroll 0 0;}\n";
        }

        $currX = 0;
        $currY = 0;
        foreach ($properties["none"]["images"] as $imageName => $imageProperties) {
            # Add the position of the image to css file
            $properties["none"]["cssCode"] .= "." . $properties['name'] . "-" . $imageName . "{background-position: -" . $currX . "px " . $currY . "px;width:" . $imageProperties["width"] . "px;height:" . $imageProperties["height"] . "px;}\n";

            self::copyImageWithTrueColors($noneRepeatImage, $imageProperties["resource"], $currX, $currY,
                0, 0, $imageProperties["width"], $imageProperties["height"]);

            $currX += $imageProperties["width"];
            $currY = 0;
            # Destroy the image resource
            imagedestroy($imageProperties["resource"]);
        }
        if (!@imagepng($noneRepeatImage, self::outputFolder . DIRECTORY_SEPARATOR . "temp_images" . DIRECTORY_SEPARATOR . $properties["name"] . ".png", 0, PNG_NO_FILTERS)) {
            throw new JotFormException(JotErrors::$SPRITE_CANNOT_SAVE_NONEREPEAT);
        }
        imagedestroy($noneRepeatImage);
    }

    private function createYRepeatSpriteImageAndCSS(&$properties)
    {
        $width = array_sum($properties["y"]["widths"]);
        $height = self::LCM($properties["y"]["heights"]);

        $yRepeatImage = self::createEmptyImage($width, $height);

        # Create the CSS Code (the background images)
        if (count($properties["y"]["images"]) > 0) {

            $cssSelectorNames = array();

            foreach ($properties["y"]["images"] as $imageName => $imageProperties) {
                $cssSelectorNames[] = $properties['name'] . "-" . $imageName;
            }

            $properties["y"]["cssCode"] .= "." . implode(", .", $cssSelectorNames)
                . "{background: transparent url(images/" . $properties["name"] . "_y.png" . ") repeat-y scroll 0 0;}\n";
        }

        $currX = 0;
        $currY = 0;
        foreach ($properties["y"]["images"] as $imageName => $imageProperties) {
            # Add the position of the image to css file
            $properties["y"]["cssCode"] .= "." . $properties['name'] . "-" . $imageName . "{background-position: -" . $currX . "px " . $currY . "px;width:" . $imageProperties["width"] . "px;}\n";

            for ($i = 0; $i < ($height / $imageProperties["height"]); $i++) {
                self::copyImageWithTrueColors($yRepeatImage, $imageProperties["resource"], $currX, $currY, 0, 0, $imageProperties["width"], $imageProperties["height"]);
                $currY += $imageProperties["height"];
            }
            $currX += $imageProperties["width"];
            $currY = 0;
            # Destroy the image resource
            imagedestroy($imageProperties["resource"]);
        }
        if (!@imagepng($yRepeatImage, self::outputFolder . DIRECTORY_SEPARATOR . "temp_images" . DIRECTORY_SEPARATOR . $properties["name"] . "_y.png", 0, PNG_NO_FILTERS)) {
            throw new JotFormException(JotErrors::$SPRITE_CANNOT_SAVE_Y_REPEAT);
        }
        imagedestroy($yRepeatImage);
    }

    private function createXRepeatSpriteImageAndCSS(&$properties)
    {
        $width = self::LCM($properties["x"]["widths"]);
        $height = array_sum($properties["x"]["heights"]);

        $xRepeatImage = self::createEmptyImage($width, $height);

        # Create the CSS Code (the background images)
        if (count($properties["x"]["images"]) > 0) {

            $cssSelectorNames = array();

            foreach ($properties["x"]["images"] as $imageName => $imageProperties) {
                $cssSelectorNames[] = $properties['name'] . "-" . $imageName;
            }

            $properties["x"]["cssCode"] .= "." . implode(", .", $cssSelectorNames)
                . "{background: transparent url( images/" . $properties["name"] . "_x.png" . ") repeat-x scroll 0 0;}\n";
        }

        $currX = 0;
        $currY = 0;
        foreach ($properties["x"]["images"] as $imageName => $imageProperties) {
            # Add the position of the image to css file
            $properties["x"]["cssCode"] .= "." . $properties['name'] . "-" . $imageName . "{background-position: " . $currX . "px -" . $currY . "px;height:" . $imageProperties["height"] . "px;}\n";

            for ($i = 0; $i < ($width / $imageProperties["width"]); $i++) {
                self::copyImageWithTrueColors($xRepeatImage, $imageProperties["resource"], $currX, $currY, 0, 0, $imageProperties["width"], $imageProperties["height"]);
                $currX += $imageProperties["width"];
            }
            $currY += $imageProperties["height"];
            $currX = 0;
            # Destroy the image resource
            imagedestroy($imageProperties["resource"]);
        }
        if (!@imagepng($xRepeatImage, self::outputFolder . DIRECTORY_SEPARATOR . "temp_images" . DIRECTORY_SEPARATOR . $properties["name"] . "_x.png", 0, PNG_NO_FILTERS)) {
            throw new JotFormException(JotErrors::$SPRITE_CANNOT_SAVE_X_REPEAT);
        }
        imagedestroy($xRepeatImage);
    }

    private function setImageProperties(&$properties, $folderName, $repeatType)
    {
        $d = dir($folderName);
        while (false !== ($entry = $d->read())) {
            $imageFilePath = $folderName . DIRECTORY_SEPARATOR . $entry;
            if (is_file($imageFilePath) && !preg_match('/^\./', $entry)) {
                # Look if file is a supported image.
                if ($imageResource = self::getImageResource($imageFilePath)) {
                    # Get the height width of the image
                    list($width, $height) = getimagesize($imageFilePath);
                    # create & check the name of the css class
                    $cssClassName = substr($entry, 0, strrpos($entry, '.'));

                    # Css class names cannot start with digit.
                    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]/', $cssClassName)) {
                        //continue;
                    }
                    # Set the properties of the image
                    $properties[$repeatType]["images"][$cssClassName] = array(
                        "path" => $imageFilePath,
                        "name" => $entry,
                        "height" => $height,
                        "width" => $width,
                        "resource" => $imageResource
                    );

                    # Set the array that will hold the all widths
                    if (!is_array($properties[$repeatType]["widths"])) {
                        $properties[$repeatType]["widths"] = array();
                    }
                    array_push($properties[$repeatType]["widths"], $width);

                    # Set the array that will hold the all heights
                    if (!is_array($properties[$repeatType]["heights"])) {
                        $properties[$repeatType]["heights"] = array();
                    }
                    array_push($properties[$repeatType]["heights"], $height);

                    # Set the string that will hold the css code for the group.
                    if (!isset($properties[$repeatType]["cssCode"])) {
                        $properties[$repeatType]["cssCode"] = "";
                    }
                }
            }
        }
        if (!count($properties[$repeatType]["images"])) {
            return false;
        }
    }

    private function getImageResource($path)
    {
        switch (exif_imagetype($path)) {
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return false;
        }
    }

    private function LCM($numbers)
    {
        $min_product = 1;
        $prime = 2;
        while (!self::check_finish($numbers)) {
            $operated = false;
            for ($v = 0; $v < count($numbers); $v++) {
                if (($numbers[$v] % $prime) == 0) {
                    $operated = true;
                    $numbers[$v] = ($numbers[$v] / $prime);;
                }
            }
            if (!$operated) {
                $found = false;
                $temp_prime = $prime;
                while (!$found) {
                    $temp_prime++;
                    for ($i = 2; $i < $temp_prime; $i++) {
                        if (($temp_prime % $i) == 0) {
                            break;
                        }
                    }
                    $prime = $temp_prime;
                    $found = true;
                }
            } else {
                $min_product = $min_product * $prime;
            }
        }
        return $min_product;
    }

    # return true if all the numbers is one
    function check_finish($numbers)
    {
        foreach ($numbers as $temp) {
            if ($temp != 1) {
                return false;
            }
        }
        return true;
    }

}