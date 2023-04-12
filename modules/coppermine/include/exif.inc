<?php 
/***************************************************************************  
   Coppermine Photo Gallery 1.3.1 for CPG-Nuke                                
  **************************************************************************  
   Port Copyright (C) 2004 Coppermine/CPG-Nuke Dev Team                        
   http://cpgnuke.com/                                               
  **************************************************************************  
   Copyright (C) 2002,2003  Grégory DEMAR <gdemar@wanadoo.fr>                 
   http://coppermine.sf.net/team/                                        
   This program is free software; you can redistribute it and/or modify       
   it under the terms of the GNU General Public License as published by       
   the Free Software Foundation; either version 2 of the License, or          
   (at your option) any later version.                                        
  **************************************************************************  
  $Header: /cvs/html/modules/coppermine/include/exif.inc,v 9.1 2005/02/18 00:16:16 trevor Exp $            
  ****************************************************************************/
if (!defined('IN_COPPERMINE')) die('Not in Coppermine...');
/**
 * PHP Class to read, write and transfer EXIF information
 * that most of the digital camera produces.
 * 
 * This class is based on jhead (in C) by Matthias Wandel
 * 
 * Vinay Yadav (vinayRas) < vinay@sanisoft.com >
 * http://www.sanisoft.com/phpexifrw/
 * 
 * For more information on EXIF
 * http://www.pima.net/standards/it10/PIMA15740/exif.htm
 * 
 * 
 * 
 * 
 * Features:
 *      - Read Exif Information
 *      - Extract and display emdedded thumbnails
 *      - Transfer Exif Information
 *      - Add Comments to files.
 *      - Tranfering EXIF information from one file to another.
 * 
 *      TODO
 *          1. Writing exif information to the file.
 *          2. Add EXIF audio reading methods (I think it exists!)
 *          3. Support of additional tags.
 *          4. Handling Unicode character in UserComment tag of EXif Information.
 */

/**
 * * * Start Of Frame N
 */
define("M_SOF0", 0xC0);
/**
 * * * N indicates which compression process
 */
define("M_SOF1", 0xC1);
/**
 * * * Only SOF0-SOF2 are now in common use
 */
define("M_SOF2", 0xC2);
/**
 */
define("M_SOF3", 0xC3);
/**
 * * * NB: codes C4 and CC are NOT SOF markers
 */
define("M_SOF5", 0xC5);
/**
 */
define("M_SOF6", 0xC6);
/**
 */
define("M_SOF7", 0xC7);
/**
 */
define("M_SOF9", 0xC9);
/**
 */
define("M_SOF10", 0xCA);
/**
 */
define("M_SOF11", 0xCB);
/**
 */
define("M_SOF13", 0xCD);
/**
 */
define("M_SOF14", 0xCE);
/**
 */
define("M_SOF15", 0xCF);
/**
 * * * Start Of Image (beginning of datastream)
 */
define("M_SOI", 0xD8);
/**
 * * * End Of Image (end of datastream)
 */
define("M_EOI", 0xD9);
/**
 * * * Start Of Scan (begins compressed data)
 */
define("M_SOS", 0xDA);
/**
 * * * Jfif marker
 */
define("M_JFIF", 0xE0);
/**
 * * * Exif marker
 */
define("M_EXIF", 0xE1);
/**
 * * * Image Title
 */
define("M_COM", 0xFE);

define("NUM_FORMATS", "12");

/**
 * * * Tag Data Format
 */
define("FMT_BYTE", "1");
/**
 * * * ASCII
 */
define("FMT_STRING", "2");
/**
 * * * Short
 */
define("FMT_USHORT", "3");
/**
 * * * Long
 */
define("FMT_ULONG", "4");
/**
 * * * Rational
 */
define("FMT_URATIONAL", "5");
/**
 * * * Byte
 */
define("FMT_SBYTE", "6");
/**
 * * * Undefined
 */
define("FMT_UNDEFINED", "7");
/**
 * * * Short
 */
define("FMT_SSHORT", "8");
/**
 * * * Long
 */
define("FMT_SLONG", "9");
/**
 * * * Rational
 */
define("FMT_SRATIONAL", "10");
/**
 * * * Single
 */
define("FMT_SINGLE", "11");
/**
 * * * Double
 */
define("FMT_DOUBLE", "12");

/**
 * * * Exif IFD
 */
define("TAG_EXIF_OFFSET", "0x8769");
/**
 * * * Interoperability tag
 */
define("TAG_INTEROP_OFFSET", "0xa005");
/**
 * * * Image input equipment manufacturer
 */
define("TAG_MAKE", "0x010F");
/**
 * * * Image input equipment model
 */
define("TAG_MODEL", "0x0110");
/**
 * * * Orientation of image
 */
define("TAG_ORIENTATION", "0x0112");
/**
 * * * Exposure Time
 */
define("TAG_EXPOSURETIME", "0x829A");
/**
 * * * F Number
 */
define("TAG_FNUMBER", "0x829D");
/**
 * * * Shutter Speed
 */
define("TAG_SHUTTERSPEED", "0x9201");
/**
 * * * Aperture
 */
define("TAG_APERTURE", "0x9202");
/**
 * * * Aperture
 */
define("TAG_MAXAPERTURE", "0x9205");
/**
 * * * Lens Focal Length
 */
define("TAG_FOCALLENGTH", "0x920A");
/**
 * * * The date and time when the original image data was generated.
 */
define("TAG_DATETIME_ORIGINAL", "0x9003");
/**
 * * * User Comments
 */
define("TAG_USERCOMMENT", "0x9286");
/**
 * * * subject Location
 */
define("TAG_SUBJECT_DISTANCE", "0x9206");
/**
 * * * Flash
 */
define("TAG_FLASH", "0x9209");
/**
 * * * Focal Plane X Resolution
 */
define("TAG_FOCALPLANEXRES", "0xa20E");
/**
 * * * Focal Plane Resolution Units
 */
define("TAG_FOCALPLANEUNITS", "0xa210");
/**
 * * * Image Width
 */
define("TAG_EXIF_IMAGEWIDTH", "0xA002");
/**
 * * * Image Height
 */
define("TAG_EXIF_IMAGELENGTH", "0xA003");
/**
 * * * Exposure Bias
 */
define("TAG_EXPOSURE_BIAS", "0x9204");
/**
 * * * Light Source
 */
define("TAG_WHITEBALANCE", "0x9208");
/**
 * * * Metering Mode
 */
define("TAG_METERING_MODE", "0x9207");
/**
 * * * Exposure Program
 */
define("TAG_EXPOSURE_PROGRAM", "0x8822");
/**
 * * * ISO Equivalent Speed Rating
 */
define("TAG_ISO_EQUIVALENT", "0x8827");
/**
 * * * Compressed Bits Per Pixel
 */
define("TAG_COMPRESSION_LEVEL", "0x9102");
/**
 * * * Thumbnail Start Offset
 */
define("TAG_THUMBNAIL_OFFSET", "0x0201");
/**
 * * * Thumbnail Length
 */
define("TAG_THUMBNAIL_LENGTH", "0x0202");
/**
 * * * Image Marker
 */
define("PSEUDO_IMAGE_MARKER", 0x123);
/**
 * * * Max Image Title Length
 */
define("MAX_COMMENT", 2000);

define("TAG_ARTIST", "0x013B");
define("TAG_COPYRIGHT", "0x8298");

/**
 * As more and more tags will be added will, the contents of array will increase.
 * DONT remove any blank array, since they do contain several tags.
 */

$FMT_BYTE_ARRAY = array();
$FMT_STRING_ARRAY = array(0x010E, // Image title
    0x010F, // Make - Image input equipment manufacturer
    0x0110, // Model - Image input equipment model
    0x0131, // Software - Software used
    0x013B, // Artist - Person who created the image
    0x8298, // Copyright - Copyright holder
    0x9003, // DateTimeOriginal - Date and time of original data generation
    );
$FMT_USHORT_ARRAY = array(0x0112, // Orientation
    0x8822, // Exposure Program
    0x9207, // Metering mode
    0x9209, // Flash
    0xA002, // Valid image width      PixelXDimension
    0xA003, // Valid image height      PixelYDimension
    );
$FMT_ULONG_ARRAY = array(0x0202, // JPEGInterchangeFormatLength
    );
$FMT_URATIONAL_ARRAY = array(0x829A, // Exposure Time
    0x829D, // F Number
    0x9102, // CompressedBitsPerPixel
    0x9202, // Aperture
    0x9205, // MaxApertureValue
    0x920A, // focal length
    );
$FMT_SBYTE_ARRAY = array();
$FMT_UNDEFINED_ARRAY = array();
$FMT_SSHORT_ARRAY = array();
$FMT_SLONG_ARRAY = array();
$FMT_SRATIONAL_ARRAY = array(0x9201, // shutter speed
    0x9204, // Exposure Bias
    );
$FMT_SINGLE_ARRAY = array();
$FMT_DOUBLE_ARRAY = array();

/**
 * * error Description
 */
/**
 * 1 - File does not exists!
 * 2 -
 * 3 - Filename not provided
 * 
 * 10 - too many padding bytes
 * 11 - "invalid marker"
 * 12 - Premature end of file?
 * 
 * 
 * 51 - "Illegal subdirectory link"
 * 52 - "NOT EXIF FORMAT"
 * 53 - "Invalid Exif alignment marker.\n"
 * 54 - "Invalid Exif start (1)"
 */

$TagTable = array(
    array(0x100, "ImageWidth"),
    array(0x101, "ImageLength"),
    array(0x102, "BitsPerSample"),
    array(0x103, "Compression"),
    array(0x106, "PhotometricInterpretation"),
    array(0x10A, "FillOrder"),
    array(0x10D, "DocumentName"),
    array(0x10E, "ImageDescription"),
    array(0x10F, "Make"),
    array(0x110, "Model"),
    array(0x111, "StripOffsets"),
    array(0x112, "Orientation"),
    array(0x115, "SamplesPerPixel"),
    array(0x116, "RowsPerStrip"),
    array(0x117, "StripByteCounts"),
    array(0x11A, "XResolution"),
    array(0x11B, "YResolution"),
    array(0x11C, "PlanarConfiguration"),
    array(0x128, "ResolutionUnit"),
    array(0x12D, "TransferFunction"),
    array(0x131, "Software"),
    array(0x132, "DateTime"),
    array(0x13B, "Artist"),
    array(0x13E, "WhitePoint"),
    array(0x13F, "PrimaryChromaticities"),
    array(0x156, "TransferRange"),
    array(0x200, "JPEGProc"),
    array(0x201, "ThumbnailOffset"),
    array(0x202, "ThumbnailLength"),
    array(0x211, "YCbCrCoefficients"),
    array(0x212, "YCbCrSubSampling"),
    array(0x213, "YCbCrPositioning"),
    array(0x214, "ReferenceBlackWhite"),
    array(0x828D, "CFARepeatPatternDim"),
    array(0x828E, "CFAPattern"),
    array(0x828F, "BatteryLevel"),
    array(0x8298, "Copyright"),
    array(0x829A, "ExposureTime"),
    array(0x829D, "FNumber"),
    array(0x83BB, "IPTC/NAA"),
    array(0x8769, "ExifOffset"),
    array(0x8773, "InterColorProfile"),
    array(0x8822, "ExposureProgram"),
    array(0x8824, "SpectralSensitivity"),
    array(0x8825, "GPSInfo"),
    array(0x8827, "ISOSpeedRatings"),
    array(0x8828, "OECF"),
    array(0x9000, "ExifVersion"),
    array(0x9003, "DateTimeOriginal"),
    array(0x9004, "DateTimeDigitized"),
    array(0x9101, "ComponentsConfiguration"),
    array(0x9102, "CompressedBitsPerPixel"),
    array(0x9201, "ShutterSpeedValue"),
    array(0x9202, "ApertureValue"),
    array(0x9203, "BrightnessValue"),
    array(0x9204, "ExposureBiasValue"),
    array(0x9205, "MaxApertureValue"),
    array(0x9206, "SubjectDistance"),
    array(0x9207, "MeteringMode"),
    array(0x9208, "LightSource"),
    array(0x9209, "Flash"),
    array(0x920A, "FocalLength"),
    array(0x927C, "MakerNote"),
    array(0x9286, "UserComment"),
    array(0x9290, "SubSecTime"),
    array(0x9291, "SubSecTimeOriginal"),
    array(0x9292, "SubSecTimeDigitized"),
    array(0xA000, "FlashPixVersion"),
    array(0xA001, "ColorSpace"),
    array(0xA002, "ExifImageWidth"),
    array(0xA003, "ExifImageLength"),
    array(0xA005, "InteroperabilityOffset"),
    array(0xA20B, "FlashEnergy"), // 0x920B in TIFF/EP
    array(0xA20C, "SpatialFrequencyResponse"), // 0x920C    -  -
    array(0xA20E, "FocalPlaneXResolution"), // 0x920E    -  -
    array(0xA20F, "FocalPlaneYResolution"), // 0x920F    -  -
    array(0xA210, "FocalPlaneResolutionUnit"), // 0x9210    -  -
    array(0xA214, "SubjectLocation"), // 0x9214    -  -
    array(0xA215, "ExposureIndex"), // 0x9215    -  -
    array(0xA217, "SensingMethod"), // 0x9217    -  -
    array(0xA300, "FileSource"),
    array(0xA301, "SceneType"),
    array(0, null)
    ) ;

$ProcessTable = array(
    array(M_SOF0, "Baseline"),
    array(M_SOF1, "Extended sequential"),
    array(M_SOF2, "Progressive"),
    array(M_SOF3, "Lossless"),
    array(M_SOF5, "Differential sequential"),
    array(M_SOF6, "Differential progressive"),
    array(M_SOF7, "Differential lossless"),
    array(M_SOF9, "Extended sequential, arithmetic coding"),
    array(M_SOF10, "Progressive, arithmetic coding"),
    array(M_SOF11, "Lossless, arithmetic coding"),
    array(M_SOF13, "Differential sequential, arithmetic coding"),
    array(M_SOF14, "Differential progressive, arithmetic coding"),
    array(M_SOF15, "Differential lossless, arithmetic coding"),
    array(0, "Unknown")
    );

/**
 * PHP Class to read, write and transfer EXIF information
 * that most of the digital camera produces
 * Currenty it can only read JPEG file.
 */
/**
 * 
 * @author Vinay Yadav (vinayRas) < vinay@sanisoft.com > 
 * @todo Writing exif information to the file.
 * @todo Add EXIF audio reading methods (I think it exists!)
 * @todo Support of additional tags.
 * @todo Handling Unicode character in UserComment tag of EXif Information.
 * @version 0.5
 * @licence http://opensource.org/licenses/lgpl-license.php GNU LGPL
 */
class phpExifRW {
    /**
     * Array containg all Exif and JPEG image attributes
     * into regular expressions for themselves.
     * $ImageInfo[TAG] = TAG_VALUE;
     * 
     * @var array 
     * @access private 
     */
    var $ImageInfo = array();

    var $MotorolaOrder = 0;
    var $ExifImageWidth = 0; //
    var $FocalplaneXRes = 0; //
    var $FocalplaneUnits = 0; //
    var $sections = array();
    var $currSection = 0;
    /**
     * * Stores total number fo Sections
     */

    var $BytesPerFormat = array(0, 1, 1, 2, 4, 8, 1, 1, 2, 4, 8, 4, 8);

    var $DirWithThumbnailPtrs = 0;
    var $ThumbnailSize = 0;

    var $ReadMode = array("READ_EXIF" => 1,
        "READ_IMAGE" => 2,
        "READ_ALL" => 3
        );

    var $ImageReadMode = 3;
    /**
     * * related to $RealMode arrays values
     */
    var $file = "";
    /**
     * * JPEG file to parse for EXIF data
     */
    var $newFile = 1;
    /**
     * * flag to check if the current file has been parsed or not.
     */

    var $thumbnail = "";
    /**
     * Name of thumbnail
     */
    var $thumbnailURL = "";
    /**
     */

    var $exifSection = -1; // market the exif section index oout of all sections
    var $errno = 0;
    var $errstr = "";

    var $debug = false;
    var $showTags = false; 
    // Caching ralated variables
    var $caching = false;
    /**
     * Should cacheing of image thumnails be allowed?
     */
    var $cacheDir = "";
    /**
     * Checkout constructor for default path.
     */

    /**
     * Constructor
     * 
     * @param string $ File name to be parsed.
     */
    function phpExifRW($file = "")
    {
        if (!empty($file)) {
            $this->file = $file;
        } 

        /**
         * Initialize some variables.
         */
        $this->ExifImageLength = 0;
        $this->ImageInfo["CCDWidth"] = 0;
        $this->ImageInfo["Distance"] = 0;
        $this->ImageInfo[M_COM] = "";
        $this->ImageInfo[TAG_FLASH] = 0;
        $this->ImageInfo[TAG_MAXAPERTURE] = 0;

        if ($this->caching) {
            $this->cacheDir = dirname(__FILE__) . "/.cache_thumbs";

            /**
             * If Cache directory does not exists then attempt to create it.
             */
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir);
            } 
            // Prepare the ame of thumbnail
            if (is_dir($this->cacheDir)) {
                $this->thumbnail = $this->cacheDir . "/" . basename($this->file);
                $this->thumbnailURL = ".cache_thumbs/" . basename($this->file);
            } 
        } 

        /**
         * * check if file exists!
         */
        if (!file_exists($this->file)) {
            $this->errno = 1;
            $this->errstr = "File '" . $this->file . "' does not exists!";
        } 
        $this->currSection = 0;
    } 

    /**
     * Show Debugging information
     * 
     * @param string $ Debugging message to display
     * @param int $ Type of error (0 - Warning, 1 - Error)
     * @return void 
     */
    function debug($str, $TYPE = 0)
    {
        if ($this->debug) {
            echo "<br />$str";
            if ($TYPE == 1) {
                exit;
            } 
        } 
    } 

    /**
     * Processes the whole file.
     */
    function processFile()
    {
        /**
         * * dont reparse the whole file.
         */
        if (!$this->newFile) return true;

        $i = 0;
        $exitAll = 0;
        /**
         * * Open the JPEG in binary safe reading mode
         */
        $fp = fopen($this->file, "rb");

        $this->ImageInfo["FileName"] = $this->file;
        $this->ImageInfo["FileSize"] = filesize($this->file);
        /**
         * * Size of the File
         */
        $this->ImageInfo["FileDateTime"] = filectime($this->file);
        /**
         * * File node change time
         */

        /**
         * * check whether jped image or not
         */
        $a = fgetc($fp);
        if (ord($a) != 0xff || ord(fgetc($fp)) != M_SOI) {
            $this->debug("Not a JPEG FILE", 1);
            $this->errorno = 1;
            $this->errorstr = "File '" . $this->file . "' does not exists!";
        } 
        $tmpTestLevel = 0;
        /**
         * * Examines each byte one-by-one
         */
        while (!feof($fp)) {
            $data = array();
            for ($a = 0;$a < 7;$a++) {
                $marker = fgetc($fp);
                if (ord($marker) != 0xff) break;
                if ($a >= 6) {
                    $this->errno = 10;
                    $this->errstr = "too many padding bytes!";
                    $this->debug($this->errstr, 1);
                    return false;
                } 
            } 

            if (ord($marker) == 0xff) {
                // 0xff is legal padding, but if we get that many, something's wrong.
                $this->errno = 10;
                $this->errstr = "too many padding bytes!";
                $this->debug($this->errstr, 1);
            } 

            $marker = ord($marker);
            $this->sections[$this->currSection]["type"] = $marker; 
            // Read the length of the section.
            $lh = ord(fgetc($fp));
            $ll = ord(fgetc($fp));

            $itemlen = ($lh << 8) | $ll;

            if ($itemlen < 2) {
                $this->errno = 11;
                $this->errstr = "invalid marker";
                $this->debug($this->errstr, 1);
            } 
            $this->sections[$this->currSection]["size"] = $itemlen;

            $tmpDataArr = array();
            /**
             * * Temporary Array
             */

            $tmpStr = fread($fp, $itemlen-2);

            $tmpDataArr[] = chr($lh);
            $tmpDataArr[] = chr($ll);

            $chars = preg_split('//', $tmpStr, -1, PREG_SPLIT_NO_EMPTY);
            $tmpDataArr = array_merge($tmpDataArr, $chars);

            $data = $tmpDataArr;
            $this->sections[$this->currSection]["data"] = $data;

            $this->debug("<hr /><h1>" . $this->currSection . ":</h1>");
            // print_r($data);
            $this->debug("<hr />");

            if (count($data) != $itemlen) {
                $this->errno = 12;
                $this->errstr = "Premature end of file?";
                $this->debug($this->errstr, 1);
            } 

            $this->currSection++;
            /**
             */

            switch ($marker) {
                case M_SOS:
                    $this->debug("<br />Found '" . M_SOS . "' Section, Prcessing it... <br />");;
                    // If reading entire image is requested, read the rest of the data.
                    if ($this->ImageReadMode &$this->ReadMode["READ_IMAGE"]) {
                        // Determine how much file is left.
                        $cp = ftell($fp);
                        fseek($fp, 0, SEEK_END);
                        $ep = ftell($fp);
                        fseek($fp, $cp, SEEK_SET);

                        $size = $ep - $cp;
                        $got = fread($fp, $size);

                        $this->sections[$this->currSection]["data"] = $got;
                        $this->sections[$this->currSection]["size"] = $size;
                        $this->sections[$this->currSection]["type"] = PSEUDO_IMAGE_MARKER;
                        $this->currSection++;
                        $HaveAll = 1;
                        $exitAll = 1;
                    } 
                    $this->debug("<br />'" . M_SOS . "' Section, PROCESSED<br />");
                    break;
                case M_COM: // Comment section
                    $this->debug("<br />Found '" . M_COM . "'(Comment) Section, Processing<br />");
                    $this->process_COM($data, $itemlen);
                    $this->debug("<br />'" . M_COM . "'(Comment) Section, PROCESSED<br />");

                    $tmpTestLevel++;
                    break;
                case M_SOI:
                    $this->debug(" <br /> === START OF IMAGE =====<br />");
                    break;
                case M_EOI:
                    $this->debug(" <br />=== END OF IMAGE =====<br /> ");
                    break;
                case M_JFIF: 
                    // Regular jpegs always have this tag, exif images have the exif
                    // marker instead, althogh ACDsee will write images with both markers.
                    // this program will re-create this marker on absence of exif marker.
                    // hence no need to keep the copy from the file.
                    // echo " <br /> === M_JFIF =====<br />";
                    $this->sections[--$this->currSection]["data"] = "";
                    break;
                case M_EXIF: 
                    // Seen files from some 'U-lead' software with Vivitar scanner
                    // that uses marker 31 for non exif stuff.  Thus make sure
                    // it says 'Exif' in the section before treating it as exif.
                    $this->debug("<br />Found '" . M_EXIF . "'(Exif) Section, Proccessing<br />");
                    $this->exifSection = $this->currSection-1;
                    if (($this->ImageReadMode &$this->ReadMode["READ_EXIF"]) && ($data[2] . $data[3] . $data[4] . $data[5]) == "Exif") {
                        $this->process_EXIF($data, $itemlen);
                    } else {
                        // Discard this section.
                        $this->sections[--$this->currSection]["data"] = "";
                    } 
                    $this->debug("<br />'" . M_EXIF . "'(Exif) Section, PROCESSED<br />");
                    $tmpTestLevel++;
                    break;
                case M_SOF0:
                case M_SOF1:
                case M_SOF2:
                case M_SOF3:
                case M_SOF5:
                case M_SOF6:
                case M_SOF7:
                case M_SOF9:
                case M_SOF10:
                case M_SOF11:
                case M_SOF13:
                case M_SOF14:
                case M_SOF15:
                    $this->debug("<br />Found M_SOFn Section, Processing<br />");
                    $this->process_SOFn($data, $marker);
                    $this->debug("<br />M_SOFn Section, PROCESSED<br />");
                    break;
                default:
                    $this->debug("DEFAULT: Jpeg section marker 0x$marker x size $itemlen\n");
            } 
            $i++;
            if ($exitAll == 1) break;
            if ($tmpTestLevel == 2) break;
        } 
        fclose($fp);
        $this->newFile = 0;
    } 

    /**
     * Changing / Assiging new file
     * 
     * @param string $ JPEG file to process
     */
    function assign($file)
    {
        if (!empty($file)) {
            $this->file = $file;
        } 

        /**
         * * check for existance of file!
         */
        if (!file_exists($this->file)) {
            $this->errorno = 1;
            $this->errorstr = "File '" . $this->file . "' does not exists!";
        } 
        $this->newFile = 1;
    } 

    /**
     * Process SOFn section of Image
     * 
     * @param array $ An array containing whole section.
     * @param hex $ Marker to specify the type of section.
     */
    function process_SOFn($data, $marker)
    {
        $data_precision = 0;
        $num_components = 0;

        $data_precision = ord($data[2]);

        if ($this->debug) {
            print("Image Dimension Calculation:");
            print("((ord($data[3]) << 8) | ord($data[4]));");
        } 
        $this->ImageInfo["Height"] = ((ord($data[3]) << 8) | ord($data[4]));
        $this->ImageInfo["Width"] = ((ord($data[5]) << 8) | ord($data[6]));

        $num_components = ord($data[7]);

        if ($num_components == 3) {
            $this->ImageInfo["IsColor"] = 1;
        } else {
            $this->ImageInfo["IsColor"] = 0;
        } 

        $this->ImageInfo["Process"] = $marker;
        $this->debug("JPEG image is " . $this->ImageInfo["Width"] . " * " . $this->ImageInfo["Height"] . ", $num_components color components, $data_precision bits per sample\n");
    } 

    /**
     * Process Comments
     * 
     * @param array $ Section data
     * @param int $ Length of the section
     */
    function process_COM($data, $length)
    {
        if ($length > MAX_COMMENT) $length = MAX_COMMENT;
        /**
         * * Truncate if it won't fit in our structure.
         */

        $nch = 0;
        for ($a = 2;$a < $length;$a++) {
            $ch = $data[$a];
            if ($ch == '\r' && $data[$a + 1] == '\n') continue; // Remove cr followed by lf.
            $Comment .= $ch;
        } 
        $this->ImageInfo[M_COM] = $Comment;
        $this->debug("COM marker comment: $Comment\n");
    } 
    /**
     * Process one of the nested EXIF directories.
     * 
     * @param string $ All directory information
     * @param string $ whole Section
     * @param int $ Length of exif section
     */
    function ProcessExifDir($DirStart, $OffsetBase, $ExifLength)
    {
        global $TagTable;

        $NumDirEntries = 0;
        $ValuePtr = array();

        $NumDirEntries = $this->Get16u($DirStart[0], $DirStart[1]);

        $this->debug("<br />Directory with $NumDirEntries entries\n");

        for ($de = 0;$de < $NumDirEntries;$de++) {
            $DirEntry = array_slice($DirStart, 2 + 12 * $de);

            $Tag = $this->Get16u($DirEntry[0], $DirEntry[1]);
            $Format = $this->Get16u($DirEntry[2], $DirEntry[3]);
            $Components = $this->Get32u($DirEntry[4], $DirEntry[5], $DirEntry[6], $DirEntry[7]);

            /**
             * if ((Format-1) >= NUM_FORMATS) {
             * // (-1) catches illegal zero case as unsigned underflows to positive large.
             * ErrNonfatal("Illegal number format %d for tag %04x", Format, Tag);
             * continue;
             * }
             */

            $ByteCount = $Components * $this->BytesPerFormat[$Format];

            if ($ByteCount > 4) {
                $OffsetVal = $this->Get32u($DirEntry[8], $DirEntry[9], $DirEntry[10], $DirEntry[11]);
                if ($OffsetVal + $ByteCount > $ExifLength) {
                    $this->debug("Illegal value pointer($OffsetVal) for tag $Tag", 1);
                } 
                $ValuePtr = array_slice($OffsetBase, $OffsetVal);
            } else {
                $ValuePtr = array_slice($DirEntry, 8);
            } 

            /**
             * if (LastExifRefd < ValuePtr+ByteCount){
             * // Keep track of last byte in the exif header that was actually referenced.
             * // That way, we know where the discardable thumbnail data begins.
             * LastExifRefd = ValuePtr+ByteCount;
             * }
             */

            if ($this->showTags) {
                for ($a = 0;;$a++) {
                    if ($TagTable[$a][0] == 0) {
                        $this->debug("  Unknown Tag $Tag Value = ");
                        break;
                    } 
                    if ($TagTable[$a][0] == $Tag) {
                        $this->debug("    " . $TagTable[$a][1] . " =");
                        break;
                    } 
                } 

                switch ($Format) {
                    case FMT_UNDEFINED: 
                    // Undefined is typically an ascii string.
                    case FMT_STRING: { // String arrays printed without function call (different from int arrays)
                            $this->debug("\""); //"
                            $str = "";
                            for ($a = 0;$a < $ByteCount;$a++) {
                                $str .= $ValuePtr[$a];
                            } 
                            $this->debug("$str\"\n"); // "
                        } 
                        break;
                    default:
                        $this->PrintFormatNumber($ValuePtr, $Format, $ByteCount);
                } // end of switch
            } // end of if  
            // Extract useful components of tag
            switch ($Tag) {
                case TAG_MAKE:
                    $this->ImageInfo[TAG_MAKE] = implode("", array_slice($ValuePtr, 0, $ByteCount));
                    break;

                case TAG_MODEL:
                    $this->ImageInfo[TAG_MODEL] = implode("", array_slice($ValuePtr, 0, $ByteCount));

                    break;

                case TAG_DATETIME_ORIGINAL:
                    $this->ImageInfo[TAG_DATETIME_ORIGINAL] = implode("", array_slice($ValuePtr, 0, $ByteCount));
                    $this->ImageInfo["DateTime"] = implode("", array_slice($ValuePtr, 0));
                    break;

                case TAG_USERCOMMENT: 
                    // Olympus has this padded with trailing spaces.  Remove these first.
                    for ($a = $ByteCount;;) {
                        $a--;
                        if ($ValuePtr[$a] == ' ') {
                            // $ValuePtr[$a] = '\0';
                        } else {
                            break;
                        } 
                        if ($a == 0) break;
                    } 
                    // Copy the comment
                    if (($ValuePtr[0] . $ValuePtr[1] . $ValuePtr[2] . $ValuePtr[3] . $ValuePtr[4]) == "ASCII") {
                        for ($a = 5;$a < 10;$a++) {
                            $c = $ValuePtr[$a];
                            if ($c != '\0' && $c != ' ') {
                                $this->ImageInfo[TAG_USERCOMMENT] = implode("", array_slice($ValuePtr, 0, $ByteCount));
                                break;
                            } 
                        } 
                    } else if (($ValuePtr[0] . $ValuePtr[1] . $ValuePtr[2] . $ValuePtr[3] . $ValuePtr[4] . $ValuePtr[5] . $ValuePtr[6]) == "Unicode") {
                        $this->ImageInfo[TAG_USERCOMMENT] = implode("", array_slice($ValuePtr, 0, $ByteCount));
                        /**
                         * Handle Unicode characters here...
                         */
                    } else {
                        $this->ImageInfo[TAG_USERCOMMENT] = implode("", array_slice($ValuePtr, 0, $ByteCount));
                    } 
                    break;

                case TAG_ARTIST:
                    $this->ImageInfo[TAG_ARTIST] = implode("", array_slice($ValuePtr, 0, $ByteCount));
                    break;

                case TAG_COPYRIGHT:
                    $this->ImageInfo[TAG_COPYRIGHT] = implode("", array_slice($ValuePtr, 0, $ByteCount));
                    break;

                case TAG_FNUMBER: 
                    // Simplest way of expressing aperture, so I trust it the most.
                    // (overwrite previously computd value if there is one)
                    $this->ImageInfo[TAG_FNUMBER] = $this->ConvertAnyFormat(implode("", array_slice($ValuePtr, 0)), $Format);
                    break;

                case TAG_APERTURE:
                case TAG_MAXAPERTURE: 
                    // More relevant info always comes earlier, so only use this field if we don't
                    // have appropriate aperture information yet.
                    if ($this->ImageInfo[TAG_MAXAPERTURE] == 0) {
                        $tmpArr = $this->ConvertAnyFormat($ValuePtr, $Format);
                        $this->ImageInfo[TAG_MAXAPERTURE] = exp($tmpArr[0] * log(2) * 0.5);
                    } 
                    break;

                case TAG_FOCALLENGTH: 
                    // Nice digital cameras actually save the focal length as a function
                    // of how farthey are zoomed in.
                    $this->ImageInfo[TAG_FOCALLENGTH] = $this->ConvertAnyFormat($ValuePtr, $Format);
                    break;

                case TAG_SUBJECT_DISTANCE: 
                    // Inidcates the distacne the autofocus camera is focused to.
                    // Tends to be less accurate as distance increases.
                    $this->ImageInfo["Distance"] = $this->ConvertAnyFormat($ValuePtr, $Format);
                    break;

                case TAG_EXPOSURETIME: 
                    // Simplest way of expressing exposure time, so I trust it most.
                    // (overwrite previously computd value if there is one)
                    $this->ImageInfo[TAG_EXPOSURETIME] = $this->ConvertAnyFormat($ValuePtr, $Format);
                    break;

                case TAG_SHUTTERSPEED: 
                    // More complicated way of expressing exposure time, so only use
                    // this value if we don't already have it from somewhere else.
                    if ($this->ImageInfo[TAG_EXPOSURETIME] == 0) {
                        $sp = $this->ConvertAnyFormat($ValuePtr, $Format);
                        $this->ImageInfo[TAG_SHUTTERSPEED] = (1 / exp($sp[0] * log(2)));
                    } 
                    break;

                case TAG_FLASH:
                    if ($this->ConvertAnyFormat($ValuePtr, $Format) &7) {
                        $this->ImageInfo[TAG_FLASH] = 1;
                    } 
                    break;

                case TAG_ORIENTATION:
                    $this->ImageInfo[TAG_ORIENTATION] = $this->ConvertAnyFormat($ValuePtr, $Format);
                    if ($this->ImageInfo[TAG_ORIENTATION] < 1 || $this->ImageInfo[TAG_ORIENTATION] > 8) {
                        $this->debug(sprintf("Undefined rotation value %d", $this->ImageInfo[TAG_ORIENTATION], 0), 1);
                        $this->ImageInfo[TAG_ORIENTATION] = 0;
                    } 
                    break;

                case TAG_EXIF_IMAGELENGTH:
                    /**
                     * Image height
                     */
                    $a = (int) $this->ConvertAnyFormat($ValuePtr, $Format);
                    if ($this->ExifImageLength < $a) $this->ExifImageLength = $a;
                    $this->ImageInfo[TAG_EXIF_IMAGELENGTH] = $this->ExifImageLength;
                    $this->ImageInfo["Height"] = $this->ExifImageLength;
                    break;
                case TAG_EXIF_IMAGEWIDTH: 
                    // Use largest of height and width to deal with images that have been
                    // rotated to portrait format.
                    $a = (int) $this->ConvertAnyFormat($ValuePtr, $Format);
                    if ($this->ExifImageWidth < $a) $this->ExifImageWidth = $a;
                    $this->ImageInfo[TAG_EXIF_IMAGEWIDTH] = $this->ExifImageWidth;
                    $this->ImageInfo["Width"] = $this->ExifImageWidth;

                    break;

                case TAG_FOCALPLANEXRES:
                    $this->FocalplaneXRes = $this->ConvertAnyFormat($ValuePtr, $Format);
                    $this->FocalplaneXRes = $this->FocalplaneXRes[0];
                    $this->ImageInfo[TAG_FOCALPLANEXRES] = $this->FocalplaneXRes[0];
                    break;

                case TAG_FOCALPLANEUNITS:
                    switch ($this->ConvertAnyFormat($ValuePtr, $Format)) {
                        case 1: $this->FocalplaneUnits = 25.4;
                            break; // inch
                        case 2: 
                            // According to the information I was using, 2 means meters.
                            // But looking at the Cannon powershot's files, inches is the only
                            // sensible value.
                            $this->FocalplaneUnits = 25.4;
                            break;

                        case 3: $this->FocalplaneUnits = 10;
                            break; // centimeter
                        case 4: $this->FocalplaneUnits = 1;
                            break; // milimeter
                        case 5: $this->FocalplaneUnits = .001;
                            break; // micrometer
                    } 
                    $this->ImageInfo[TAG_FOCALPLANEUNITS] = $this->FocalplaneUnits;
                    break; 
                // Remaining cases contributed by: Volker C. Schoech (schoech@gmx.de)
                case TAG_EXPOSURE_BIAS:
                    $this->ImageInfo[TAG_EXPOSURE_BIAS] = $this->ConvertAnyFormat($ValuePtr, $Format);
                    break;

                case TAG_WHITEBALANCE:
                    $this->ImageInfo[TAG_WHITEBALANCE] = (int) $this->ConvertAnyFormat($ValuePtr, $Format);
                    break;

                case TAG_METERING_MODE:
                    $this->ImageInfo[TAG_METERING_MODE] = (int) $this->ConvertAnyFormat($ValuePtr, $Format);
                    break;

                case TAG_EXPOSURE_PROGRAM:
                    $this->ImageInfo[TAG_EXPOSURE_PROGRAM] = (int) $this->ConvertAnyFormat($ValuePtr, $Format);
                    break;

                case TAG_ISO_EQUIVALENT:
                    $this->ImageInfo[TAG_ISO_EQUIVALENT] = (int) $this->ConvertAnyFormat($ValuePtr, $Format);
                    if ($this->ImageInfo[TAG_ISO_EQUIVALENT] < 50) $this->ImageInfo[TAG_ISO_EQUIVALENT] *= 200;
                    break;

                case TAG_COMPRESSION_LEVEL:
                    $this->ImageInfo[TAG_COMPRESSION_LEVEL] = (int) $this->ConvertAnyFormat($ValuePtr, $Format);
                    break;

                case TAG_THUMBNAIL_OFFSET:
                    $this->ThumbnailOffset = $this->ConvertAnyFormat($ValuePtr, $Format);
                    $this->DirWithThumbnailPtrs = $DirStart;
                    break;

                case TAG_THUMBNAIL_LENGTH:
                    $this->ThumbnailSize = $this->ConvertAnyFormat($ValuePtr, $Format);
                    $this->ImageInfo[TAG_THUMBNAIL_LENGTH] = $this->ThumbnailSize;
                    break;

                case TAG_EXIF_OFFSET:
                case TAG_INTEROP_OFFSET: {
                        $SubdirStart = array_slice($OffsetBase, $this->Get32u($ValuePtr[0], $ValuePtr[1], $ValuePtr[2], $ValuePtr[3])); 
                        // if ($SubdirStart < $OffsetBase || $SubdirStart > $OffsetBase+$ExifLength){
                        // debug("Illegal exif or interop ofset directory link",1);
                        // }else{
                        $this->ProcessExifDir($SubdirStart, $OffsetBase, $ExifLength); 
                        // }
                        continue;
                    } 
            } 
        } {
            // In addition to linking to subdirectories via exif tags,
            // there's also a potential link to another directory at the end of each
            // directory.  this has got to be the result of a comitee!
            $tmpDirStart = array_slice($DirStart, 2 + 12 * $NumDirEntries);
            if (count($tmpDirStart) + 4 <= count($OffsetBase) + $ExifLength) {
                $Offset = $this->Get32u($tmpDirStart[0], $tmpDirStart[1], $tmpDirStart[2], $tmpDirStart[3]);
                if ($Offset) {
                    $SubdirStart = array_slice($OffsetBase, $Offset);
                    if (count($SubdirStart) > count($OffsetBase) + $ExifLength) {
                        if (count($SubdirStart) < count($OffsetBase) + $ExifLength + 20) {
                            // Jhead 1.3 or earlier would crop the whole directory!
                            // As Jhead produces this form of format incorrectness,
                            // I'll just let it pass silently
                            // if (ShowTags) printf("Thumbnail removed with Jhead 1.3 or earlier\n");
                        } else {
                            $this->errno = 51;
                            $this->errstr = "Illegal subdirectory link";
                            $this->debug($this->errstr, 1);
                        } 
                    } else {
                        if (count($SubdirStart) <= count($OffsetBase) + $ExifLength) {
                            $this->ProcessExifDir($SubdirStart, $OffsetBase, $ExifLength);
                        } 
                    } 
                } 
            } else {
                // The exif header ends before the last next directory pointer.
            } 
        } 

        /**
         * Check if thumbnail has been cached or not.
         * If yes! then read the file.
         */

        if (file_exists($this->thumbnail) && $this->caching && (filemtime($this->thumbnail) == filemtime($this->file))) {
            $fp = fopen($this->thumbnail, "rb");
            $tmpStr = fread($fp, filesize($this->thumbnail));

            $this->ImageInfo["ThumbnailPointer"] = preg_split('//', $tmpStr, -1, PREG_SPLIT_NO_EMPTY);
            $this->ImageInfo["ThumbnailSize"] = filesize($this->thumbnail);
        } else {
            if ($this->ThumbnailSize && $this->ThumbnailOffset) {
                if ($this->ThumbnailSize + $this->ThumbnailOffset <= $ExifLength) {
                    // The thumbnail pointer appears to be valid.  Store it.
                    $this->ImageInfo["ThumbnailPointer"] = array_slice($OffsetBase, $this->ThumbnailOffset);
                    $this->ImageInfo["ThumbnailSize"] = $this->ThumbnailSize;

                    /**
                     * Save the thumbnail
                     */
                    if ($this->caching && is_dir($this->cacheDir)) {
                        $this->saveThumbnail($this->thumbnail);
                    } 
                } 
            } 
        } 
        $this->debug(sprintf("Thumbnail size: %d bytes\n", $this->ThumbnailSize), "TAGS");
    } 

    /**
     * Process Exif data
     * 
     * @param array $ Section data as an array
     * @param int $ Length of the section (length of data array)
     */
    function process_EXIF($data, $length)
    {
        $this->debug("Exif header $length bytes long\n");
        if (($data[2] . $data[3] . $data[4] . $data[5]) != "Exif") {
            $this->errno = 52;
            $this->errstr = "NOT EXIF FORMAT";
            $this->debug($this->errstr, 1);
        } 

        $this->ImageInfo["FlashUsed"] = 0;
        /**
         * * If it s from a digicam, and it used flash, it says so.
         */

        $this->FocalplaneXRes = 0;
        $this->FocalplaneUnits = 0;
        $this->ExifImageWidth = 0;

        if (($data[8] . $data[9]) == "II") {
            $this->debug("Exif section in Intel order\n");
            $this->MotorolaOrder = 0;
        } else if (($data[8] . $data[9]) == "MM") {
            $this->debug("Exif section in Motorola order\n");
            $this->MotorolaOrder = 1;
        } else {
            $this->errno = 53;
            $this->errstr = "Invalid Exif alignment marker.\n";
            $this->debug($this->errstr, 1);
            return;
        } 

        if ($this->Get16u($data[10], $data[11]) != 0x2A || $this->Get32s($data[12], $data[13], $data[14], $data[15]) != 0x08) {
            $this->errno = 54;
            $this->errstr = "Invalid Exif start (1)";
            $this->debug($this->errstr, 1);
        } 

        $DirWithThumbnailPtrs = null;

        $this->ProcessExifDir(array_slice($data, 16), array_slice($data, 8), $length); 
        // Compute the CCD width, in milimeters.                      2
        if ($this->FocalplaneXRes != 0) {
            $this->ImageInfo["CCDWidth"] = (float)($this->ExifImageWidth * $this->FocalplaneUnits / $this->FocalplaneXRes);
        } 

        $this->debug("Non settings part of Exif header: " . $length . " bytes\n");
    } // end of function process_EXIF
    /**
     * Converts two byte number into its equivalent int integer
     * 
     * @param int $ 
     * @param int $ 
     */
    function Get16u($val, $by)
    {
        if ($this->MotorolaOrder) {
            return ((ord($val) << 8) | ord($by));
        } else {
            return ((ord($by) << 8) | ord($val));
        } 
    } 

    /**
     * Converts 4-byte number into its equivalent integer
     * 
     * @param int $ 
     * @param int $ 
     * @param int $ 
     * @param int $ 
     * @return int 
     */
    function Get32s($val1, $val2, $val3, $val4)
    {
        $val1 = ord($val1);
        $val2 = ord($val2);
        $val3 = ord($val3);
        $val4 = ord($val4);

        if ($this->MotorolaOrder) {
            return (($val1 << 24) | ($val2 << 16) | ($val3 << 8) | ($val4 << 0));
        } else {
            return (($val4 << 24) | ($val3 << 16) | ($val2 << 8) | ($val1 << 0));
        } 
    } 
    /**
     * Converts 4-byte number into its equivalent integer with the help of Get32s
     * 
     * @param int $ 
     * @param int $ 
     * @param int $ 
     * @param int $ 
     * @return int 
     */
    function get32u($val1, $val2, $val3, $val4)
    {
        return ($this->Get32s($val1, $val2, $val3, $val4) &0xffffffff);
    } 

    /**
     * needed for examining exif header and printng the value of a tag
     * 
     * @param array $ 
     * @param int $ 
     * @param int $ 
     */
    function PrintFormatNumber($ValuePtr, $Format, $ByteCount)
    {
        switch ($Format) {
            case FMT_SBYTE:
            case FMT_BYTE: printf("%02x\n", $ValuePtr[0]);
                break;
            case FMT_USHORT: printf("%d\n", $this->Get16u($ValuePtr[0], $ValuePtr[1]));
                break;
            case FMT_ULONG:
            case FMT_SLONG: printf("%d\n", $this->Get32s($ValuePtr[0], $ValuePtr[1], $ValuePtr[2], $ValuePtr[3]));
                break;
            case FMT_SSHORT: printf("%hd\n", $this->Get16u($ValuePtr[0], $ValuePtr[1]));
                break;
            case FMT_URATIONAL:
            case FMT_SRATIONAL:
                printf("%d/%d\n", $this->Get32s($ValuePtr[0], $ValuePtr[1], $ValuePtr[2], $ValuePtr[3]), $this->Get32s($ValuePtr[4], $ValuePtr[5], $ValuePtr[6], $ValuePtr[7]));
                break;

            case FMT_SINGLE: printf("%f\n", $ValuePtr[0]);
                break;
            case FMT_DOUBLE: printf("%f\n", $ValuePtr[0]);
                break;
            default:
                printf("Unknown format %d:", $Format);
        } 
    } 
    // --------------------------------------------------------------------------
    // Evaluate number, be it int, rational, or float from directory.
    // --------------------------------------------------------------------------
    function ConvertAnyFormat($ValuePtr, $Format)
    {
        $Value = 0;

        switch ($Format) {
            case FMT_SBYTE: $Value = $ValuePtr[0];
                break;
            case FMT_BYTE: $Value = $ValuePtr[0];
                break;

            case FMT_USHORT: $Value = $this->Get16u($ValuePtr[0], $ValuePtr[1]);
                break;
            case FMT_ULONG: $Value = $this->Get32u($ValuePtr[0], $ValuePtr[1], $ValuePtr[2], $ValuePtr[3]);
                break;

            case FMT_URATIONAL:
            case FMT_SRATIONAL: {
                    $Num = $this->Get32s($ValuePtr[0], $ValuePtr[1], $ValuePtr[2], $ValuePtr[3]);
                    $Den = $this->Get32s($ValuePtr[4], $ValuePtr[5], $ValuePtr[6], $ValuePtr[7]);
                    if ($Den == 0) {
                        $Value = 0;
                    } else {
                        $Value = (double) ($Num / $Den);
                    } 
                    return array($Value, array($Num, $Den));
                    break;
                } 

            case FMT_SSHORT: $Value = $this->Get16u($ValuePtr[0], $ValuePtr[1]);
                break;
            case FMT_SLONG: $Value = $this->Get32s($ValuePtr[0], $ValuePtr[1], $ValuePtr[2], $ValuePtr[3]);
                break; 
            // Not sure if this is correct (never seen float used in Exif format)
            case FMT_SINGLE: $Value = $ValuePtr[0];
                break;
            case FMT_DOUBLE: $Value = $ValuePtr[0];
                break;
        } 
        return $Value;
    } 

    /**
     * Reverse of ConvertAnyFormat, - Incomplete
     * TODO:
     * only FMT_URATIONAL, FMT_SRATIONAL works
     */
    function ConvertAnyFormatBack($Value, $Format)
    { 
        // $Value = 0;
        switch ($Format) {
            case FMT_SBYTE: $Value = $ValuePtr[0];
                break;
            case FMT_BYTE: $Value = $ValuePtr[0];
                break;

            case FMT_USHORT: $Value = $this->Get16u($ValuePtr[0], $ValuePtr[1]);
                break;
            case FMT_ULONG: $Value = $this->Get32u($ValuePtr[0], $ValuePtr[1], $ValuePtr[2], $ValuePtr[3]);
                break;

            case FMT_URATIONAL:
            case FMT_SRATIONAL: {
                    $num = $Value[1][0];
                    $Den = $Value[1][1];

                    $ValuePtr[0] = chr($num >> 24);
                    $ValuePtr[1] = chr($num >> 16);
                    $ValuePtr[2] = chr($num >> 8);
                    $ValuePtr[3] = chr($num);

                    $ValuePtr[4] = chr($Den >> 24);
                    $ValuePtr[5] = chr($Den >> 16);
                    $ValuePtr[6] = chr($Den >> 8);
                    $ValuePtr[7] = chr($Den);

                    break;
                } 

            case FMT_SSHORT: $Value = $this->Get16u($ValuePtr[0], $ValuePtr[1]);
                break;
            case FMT_SLONG: $Value = $this->Get32s($ValuePtr[0], $ValuePtr[1], $ValuePtr[2], $ValuePtr[3]);
                break; 
            // Not sure if this is correct (never seen float used in Exif format)
            case FMT_SINGLE: $Value = $ValuePtr[0];
                break;
            case FMT_DOUBLE: $Value = $ValuePtr[0];
                break;
            default:
                return -1;
        } 
        return $ValuePtr;
    } 
    /**
     * Function to extract thumbnail from Exif data of the image.
     * and store it in a filename given by $ThumbFile
     * 
     * @param String $ Files name to store the thumbnail
     */
    function saveThumbnail($ThumbFile)
    {
        $ThumbFile = trim($ThumbFile);
        $file = basename($this->file);

        if (empty($ThumbFile)) $ThumbFile = "th_$file";

        if (count($this->ImageInfo["ThumbnailPointer"]) > 0) {
            $tp = fopen($ThumbFile, "wb");
            if (!$tp) {
                $this->errno = 2;
                $this->errstr = "Cannot Open file '$ThumbFile'";
            } 
            fwrite($tp, implode("", $this->ImageInfo["ThumbnailPointer"]), $this->ImageInfo["ThumbnailSize"]);
            fclose($tp);
            touch($ThumbFile, filemtime($this->file));
        } 
        $this->thumbnail = $ThumbFile;
    } 

    /**
     * Returns thumbnail url along with parameter supplied.
     * Should be called in src attribute of image
     * 
     * @return string File URL
     */
    function showThumbnail()
    {
        return "showThumbnail.php?file=" . $this->file;
    } 

    /**
     * Function to give back the whole image
     * 
     * @return string full image
     */
    function getThumbnail()
    {
        /**
         * if($this->caching && !empty($this->cacheDir)) {
         * $fp = fopen($thumbFilename,"rb");
         * $tmpStr = fread($fp,filesize($thumbFilename));
         * $this->ImageInfo["ThumbnailPointer"] = preg_split('//', $tmpStr, -1, PREG_SPLIT_NO_EMPTY);
         * }
         */
        return implode("", $this->ImageInfo["ThumbnailPointer"]);
    } 

    /**
     * Display the extracted Exif information. It can overloaded for the look to change.
     * 
     * @return void 
     */
    function showImageInfo()
    {
        global $ProcessTable;
        $tmpDebug = $this->debug;
        $this->debug = true;
        $this->debug(sprintf("File name    : %s\n", $this->ImageInfo["FileName"]));
        $this->debug(sprintf("File size    : %d bytes\n", $this->ImageInfo["FileSize"])); {
            $this->debug(sprintf("File date    : %s\n", date("d-M-Y H:i:s", $this->ImageInfo["FileDateTime"])));
        } 

        if ($this->ImageInfo[TAG_MAKE]) {
            $this->debug(sprintf("Camera make  : %s\n", $this->ImageInfo[TAG_MAKE]));
            $this->debug(sprintf("Camera model : %s\n", $this->ImageInfo[TAG_MODEL]));
        } 
        if ($this->ImageInfo["DateTime"]) {
            $this->debug(sprintf("Date/Time    : %s\n", $this->ImageInfo[TAG_DATETIME_ORIGINAL]));
        } 

        $this->debug(sprintf("Resolution   : %d x %d\n", $this->ImageInfo["Width"], $this->ImageInfo["Height"]));

        if ($this->ImageInfo[TAG_ORIENTATION] > 1) {
            // Only print orientation if one was supplied, and if its not 1 (normal orientation)
            // 1 - "The 0th row is at the visual top of the image,    and the 0th column is the visual left-hand side."
            // 2 - "The 0th row is at the visual top of the image,    and the 0th column is the visual right-hand side."
            // 3 - "The 0th row is at the visual bottom of the image, and the 0th column is the visual right-hand side."
            // 4 - "The 0th row is at the visual bottom of the image, and the 0th column is the visual left-hand side."
            // 5 - "The 0th row is the visual left-hand side of of the image,  and the 0th column is the visual top."
            // 6 - "The 0th row is the visual right-hand side of of the image, and the 0th column is the visual top."
            // 7 - "The 0th row is the visual right-hand side of of the image, and the 0th column is the visual bottom."
            // 8 - "The 0th row is the visual left-hand side of of the image,  and the 0th column is the visual bottom."
            // Note: The descriptions here are the same as the name of the command line
            // ption to pass to jpegtran to right the image
            $OrientTab = array("Undefined",
                "Normal", // 1
                "flip horizontal", // left right reversed mirror
                "rotate 180", // 3
                "flip vertical", // upside down mirror
                "transpose", // Flipped about top-left <--> bottom-right axis.
                "rotate 90", // rotate 90 cw to right it.
                "transverse", // flipped about top-right <--> bottom-left axis
                "rotate 270", // rotate 270 to right it.
                );

            $this->debug(sprintf("Orientation  : %s\n", $OrientTab[$this->ImageInfo[TAG_ORIENTATION]]));
        } 

        if ($this->ImageInfo["IsColor"] == 0) {
            $this->debug(sprintf("Color/bw     : Black and white\n"));
        } 
        if ($this->ImageInfo[TAG_FLASH] >= 0) {
            $this->debug(sprintf("Flash used   : %s\n", $this->ImageInfo[TAG_FLASH] ? "Yes" :"No"));
        } 
        if ($this->ImageInfo[TAG_FOCALLENGTH]) {
            $this->debug(sprintf("Focal length : %4.1fmm (%s/%s)", (double)$this->ImageInfo[TAG_FOCALLENGTH][0], $this->ImageInfo[TAG_FOCALLENGTH][1][0], $this->ImageInfo[TAG_FOCALLENGTH][1][1]));
            if ($this->ImageInfo["CCDWidth"]) {
                $this->debug(sprintf("  (35mm equivalent: %dmm)",
                        (int)($this->ImageInfo["FocalLength"] / $this->ImageInfo["CCDWidth"] * 36 + 0.5)));
            } 
        } 

        if ($this->ImageInfo["CCDWidth"]) {
            $this->debug(sprintf("CCD width    : %4.2fmm\n", (double)$this->ImageInfo["CCDWidth"]));
        } 

        if ($this->ImageInfo[TAG_EXPOSURETIME]) {
            $this->debug(sprintf("Exposure time:%6.3f s (%d/%d)", (double)$this->ImageInfo[TAG_EXPOSURETIME][0],
                    $this->ImageInfo[TAG_EXPOSURETIME][1][0], $this->ImageInfo[TAG_EXPOSURETIME][1][1]));
            if ($this->ImageInfo[TAG_EXPOSURETIME] <= 0.5) {
                $this->debug(sprintf(" (1/%d)", (int)(0.5 + 1 / $this->ImageInfo[TAG_EXPOSURETIME][0])));
            } 
        } 
        if ($this->ImageInfo[TAG_FNUMBER]) {
            $this->debug(sprintf("Aperture     : f/%3.1f\n", (double)$this->ImageInfo[TAG_FNUMBER][0]));
        } 
        if ($this->ImageInfo["Distance"]) {
            if ($this->ImageInfo["Distance"] < 0) {
                $this->debug("Focus dist.  : Infinite\n");
            } else {
                $this->debug(sprintf("Focus dist.  : %4.2fm\n", (double)$this->ImageInfo["Distance"]));
            } 
        } 

        if ($this->ImageInfo[TAG_ISO_EQUIVALENT]) { // 05-jan-2001 vcs
            $this->debug(sprintf("ISO equiv.   : %2d\n", (int)$this->ImageInfo[TAG_ISO_EQUIVALENT]));
        } 
        if ($this->ImageInfo[TAG_EXPOSURE_BIAS]) { // 05-jan-2001 vcs
            $this->debug(sprintf("Exposure bias:%4.2f (%d/%d)\n", (double)$this->ImageInfo[TAG_EXPOSURE_BIAS][0],
                    $this->ImageInfo[TAG_EXPOSURE_BIAS][1][0], $this->ImageInfo[TAG_EXPOSURE_BIAS][1][1]));
        } 

        if ($this->ImageInfo[TAG_WHITEBALANCE]) { // 05-jan-2001 vcs
            switch ($this->ImageInfo[TAG_WHITEBALANCE]) {
                case 1:
                    $this->debug("Whitebalance : sunny\n");
                    break;
                case 2:
                    $this->debug("Whitebalance : fluorescent\n");
                    break;
                case 3:
                    $this->debug("Whitebalance : incandescent\n");
                    break;
                default:
                    $this->debug("Whitebalance : cloudy\n");
            } 
        } 
        if ($this->ImageInfo[TAG_METERING_MODE]) { // 05-jan-2001 vcs
            switch ($this->ImageInfo[TAG_METERING_MODE]) {
                case 2:
                    $this->debug("Metering Mode: center weight\n");
                    break;
                case 3:
                    $this->debug("Metering Mode: spot\n");
                    break;
                case 5:
                    $this->debug("Metering Mode: matrix\n");
                    break;
            } 
        } 
        if ($this->ImageInfo[TAG_EXPOSURE_PROGRAM]) { // 05-jan-2001 vcs
            switch ($this->ImageInfo[TAG_EXPOSURE_PROGRAM]) {
                case 2:
                    $this->debug("Exposure     : program (auto)\n");
                    break;
                case 3:
                    $this->debug("Exposure     : aperture priority (semi-auto)\n");
                    break;
                case 4:
                    $this->debug("Exposure     : shutter priority (semi-auto)\n");
                    break;
            } 
        } 
        if ($this->ImageInfo[TAG_COMPRESSION_LEVEL]) { // 05-jan-2001 vcs
            switch ($this->ImageInfo[TAG_COMPRESSION_LEVEL]) {
                case 1:
                    $this->debug("Jpeg Quality : basic\n");
                    break;
                case 2:
                    $this->debug("Jpeg Quality : normal\n");
                    break;
                case 4:
                    $this->debug("Jpeg Quality : fine\n");
                    break;
            } 
        } 

        for ($a = 0;;$a++) {
            if ($ProcessTable[$a][0] == $this->ImageInfo["Process"] || $ProcessTable[$a][0] == 0) {
                $this->debug("Jpeg process : " . $ProcessTable[$a][1]);
                break;
            } 
        } 
        // Print the comment. Print 'Comment:' for each new line of comment.
        if ($this->ImageInfo[TAG_USERCOMMENT]) {
            $this->debug("Exif Comment      : " . $this->ImageInfo[TAG_USERCOMMENT]);
        } 
        // Print the comment. Print 'Comment:' for each new line of comment.
        if ($this->ImageInfo[M_COM]) {
            $this->debug("Image Comment      : " . htmlprepare($this->ImageInfo[M_COM]));
        } 
        // Print the comment. Print 'Comment:' for each new line of comment.
        if ($this->ImageInfo[TAG_ARTIST]) {
            $this->debug("Artist : " . $this->ImageInfo[TAG_ARTIST]);
        } 

        if ($this->ImageInfo[TAG_COPYRIGHT]) {
            $this->debug("Copyright : " . htmlprepare($this->ImageInfo[TAG_COPYRIGHT]));
        } 

        if ($this->thumbnailURL) {
            $this->debug("<a href='$this->thumbnailURL'>$this->thumbnailURL</a>");
            /**
             * * create a  link to view extracted thumbnail
             */
        } 
        $this->debug = $tmpDebug;
    } // end of show function
    /**
     * return the extracted Exif information as a human readable array.
     * 
     * @return array 
     */
    function getImageInfo()
    {
        global $ProcessTable;
        $getArray = array();
        $getArray["FileName"] = $this->ImageInfo["FileName"];
        $getArray["FileSize"] = $this->ImageInfo["FileSize"];

        $getArray["FileDate"] = date("d-M-Y H:i:s", $this->ImageInfo["FileDateTime"]);

        if ($this->ImageInfo[TAG_MAKE]) {
            $getArray["Make"] = $this->ImageInfo[TAG_MAKE];
            $getArray["Model"] = $this->ImageInfo[TAG_MODEL];
        } 
        if ($this->ImageInfo["DateTime"]) {
            $getArray["ExposureDate"] = $this->ImageInfo[TAG_DATETIME_ORIGINAL];
        } 

        $getArray["Resolution"] = sprintf("%d x %d", $this->ImageInfo["Width"], $this->ImageInfo["Height"]);

        if ($this->ImageInfo[TAG_ORIENTATION] > 1) {
            // Only print orientation if one was supplied, and if its not 1 (normal orientation)
            // 1 - "The 0th row is at the visual top of the image,    and the 0th column is the visual left-hand side."
            // 2 - "The 0th row is at the visual top of the image,    and the 0th column is the visual right-hand side."
            // 3 - "The 0th row is at the visual bottom of the image, and the 0th column is the visual right-hand side."
            // 4 - "The 0th row is at the visual bottom of the image, and the 0th column is the visual left-hand side."
            // 5 - "The 0th row is the visual left-hand side of of the image,  and the 0th column is the visual top."
            // 6 - "The 0th row is the visual right-hand side of of the image, and the 0th column is the visual top."
            // 7 - "The 0th row is the visual right-hand side of of the image, and the 0th column is the visual bottom."
            // 8 - "The 0th row is the visual left-hand side of of the image,  and the 0th column is the visual bottom."
            // Note: The descriptions here are the same as the name of the command line
            // ption to pass to jpegtran to right the image
            $OrientTab = array("Undefined",
                "Normal", // 1
                "flip horizontal", // left right reversed mirror
                "rotate 180", // 3
                "flip vertical", // upside down mirror
                "transpose", // Flipped about top-left <--> bottom-right axis.
                "rotate 90", // rotate 90 cw to right it.
                "transverse", // flipped about top-right <--> bottom-left axis
                "rotate 270", // rotate 270 to right it.
                );

            $getArray["Orientation"] = $OrientTab[$this->ImageInfo[TAG_ORIENTATION]];
        } 

        $getArray["Color"] = ($this->ImageInfo["IsColor"] == 0) ? "Black and White" : "Color";
        $getArray["Flash"] = $this->ImageInfo[TAG_FLASH] ? "Yes" :"No";

        if ($this->ImageInfo[TAG_FOCALLENGTH]) {
            $getArray["FocalLength"] = (sprintf("%4.1fmm (%s/%s)", (double)$this->ImageInfo[TAG_FOCALLENGTH][0], $this->ImageInfo[TAG_FOCALLENGTH][1][0], $this->ImageInfo[TAG_FOCALLENGTH][1][1]));
            if ($this->ImageInfo["CCDWidth"]) {
                $getArray["35mmEqui"] = (sprintf("%dmm",
                        (int)($this->ImageInfo["FocalLength"] / $this->ImageInfo["CCDWidth"] * 36 + 0.5)));
            } 
        } 

        if ($this->ImageInfo[TAG_EXPOSURETIME]) {
            $getArray["ExposureTime"] = (sprintf("%6.3f s (%d/%d)", (double)$this->ImageInfo[TAG_EXPOSURETIME][0],
                    $this->ImageInfo[TAG_EXPOSURETIME][1][0], $this->ImageInfo[TAG_EXPOSURETIME][1][1]));

            if ($this->ImageInfo[TAG_EXPOSURETIME] <= 0.5) {
                $getArray["ExposureTime"] = (sprintf(" (1/%d)", (int)(0.5 + 1 / $this->ImageInfo[TAG_EXPOSURETIME][0])));
            } 
        } 
        if ($this->ImageInfo[TAG_FNUMBER]) {
            $getArray["Aperture"] = (sprintf("f/%3.1f", (double)$this->ImageInfo[TAG_FNUMBER][0]));
        } 
        if ($this->ImageInfo["Distance"]) {
            if ($this->ImageInfo["Distance"] < 0) {
                $getArray["Distance"] = "Infinite";
            } else {
                $getArray["Distance"] = sprintf("%4.2fm", (double)$this->ImageInfo["Distance"]);
            } 
        } 

        if ($this->ImageInfo[TAG_ISO_EQUIVALENT]) { // 05-jan-2001 vcs
            $getArray["ISO equiv"] = sprintf("%2d", (int)$this->ImageInfo[TAG_ISO_EQUIVALENT]);
        } 
        if ($this->ImageInfo[TAG_EXPOSURE_BIAS]) { // 05-jan-2001 vcs
            $getArray["ExposureBias"] = (sprintf("%4.2f (%d/%d)", (double)$this->ImageInfo[TAG_EXPOSURE_BIAS][0],
                    $this->ImageInfo[TAG_EXPOSURE_BIAS][1][0], $this->ImageInfo[TAG_EXPOSURE_BIAS][1][1]));
        } 

        if ($this->ImageInfo[TAG_WHITEBALANCE]) { // 05-jan-2001 vcs
            switch ($this->ImageInfo[TAG_WHITEBALANCE]) {
                case 1:
                    $getArray["Whitebalance"] = "sunny";
                    break;
                case 2:
                    $getArray["Whitebalance"] = "fluorescent";
                    break;
                case 3:
                    $getArray["Whitebalance"] = "incandescent";
                    break;
                default:
                    $getArray["Whitebalance"] = "cloudy";
            } 
        } 
        if ($this->ImageInfo[TAG_METERING_MODE]) { // 05-jan-2001 vcs
            switch ($this->ImageInfo[TAG_METERING_MODE]) {
                case 2:
                    $getArray["MeteringMode"] = "center weight";
                    break;
                case 3:
                    $getArray["MeteringMode"] = "spot";
                    break;
                case 5:
                    $getArray["MeteringMode"] = "matrix";
                    break;
            } 
        } 

        if ($this->ImageInfo[TAG_USERCOMMENT]) {
            $getArray["comment"] = $this->ImageInfo[TAG_USERCOMMENT];
        } 

        return $getArray;
    } // end of get function
    /**
     * Modifies or sets value of specified Tag -
     * 
     * @param hex $ Tag, whose value has to be set
     * @param string $ Tags value
     */
    function setExifData($param, $value)
    {
        $this->ImageInfo["$param"] = $value;
    } 

    /**
     * This functiion writes back the modifed exif data into the imageinfo array - INCOMPLETE
     */
    function modifyExifDetails()
    {
        $newData[0] = $this->sections[$this->exifSection]["data"][0];
        $newData[1] = $this->sections[$this->exifSection]["data"][1];

        $newData[2] = 'E';
        $newData[3] = 'x';
        $newData[4] = 'i';
        $newData[5] = 'f';
        $newData[6] = $this->sections[$this->exifSection]["data"][6];
        $newData[7] = $this->sections[$this->exifSection]["data"][7];

        if ($this->MotorolaOrder == 1) {
            $newData[8] = 'M';
            $newData[9] = 'M';
        } else {
            $newData[8] = 'I';
            $newData[9] = 'I';
        } 

        $newData[10] = chr(42 >> 8);
        $newData[11] = chr(42);

        $newData[12] = chr(0);
        $newData[13] = chr(0);
        $newData[14] = chr(0);
        $newData[15] = chr(8);

        $newData[16] = 1;
        $newData[17] = 1;

        $totalLength = 16;
        $totalElements = 0;
        $offset = 10 + (15 * 12);
        foreach($this->ImageInfo as $tag => $val) {
            if (eregi("0x", $tag)) {
                $tmpTag = hexdec($tag); 
                // tag
                $newData[] = chr($tmpTag >> 8);
                $newData[] = chr($tmpTag); 
                // format
                $fmt = $this->getFormat($tag);
                $newData[] = chr($fmt >> 8);
                $newData[] = chr($fmt); 
                // components
                $chars = preg_split('//', $val, -1, PREG_SPLIT_NO_EMPTY);
                $ByteCount = count($chars);

                $Components = ceil($ByteCount / $this->BytesPerFormat[$fmt]);

                $newData[] = chr($Components >> 24);
                $newData[] = chr($Components >> 16);
                $newData[] = chr($Components >> 8);
                $newData[] = chr($Components);

                if ($ByteCount <= 4) {
                    $newData[] = chr($chars[0] >> 8);
                    $newData[] = chr($chars[0]);
                    $newData[] = chr($chars[2]);
                    $newData[] = chr($chars[3]);
                } else {
                    $newData[] = chr($offset >> 24);
                    $newData[] = chr($offset >> 16);
                    $newData[] = chr($offset >> 8);
                    $newData[] = chr($offset);

                    if ($fmt != FMT_STRING) {
                        $arr = $this->ConvertAnyFormatBack($val, $fmt);
                        $chars = $arr;
                        $ByteCount = 8;
                    } 
                    $offset += $ByteCount;

                    $otherDataArr = array_merge($otherDataArr, $chars);
                } 
                $totalLength += 12 + $ByteCount;
                $totalElements++;
            } 
        } 
        $newData = array_merge($newData, $otherDataArr);

        /**
         * Write the thumbnail back to the exif section 
         * Dont know if this works -
         */
        /**
         * if($this->thumbnail) {
         * echo "Thumnail Size:".count($this->ImageInfo["ThumbnailPointer"]);
         * 
         * $tmpTag = hexdec();
         * // tag
         * $newData[] = chr($tmpTag >> 8);
         * $newData[] = chr($tmpTag);
         * 
         * // format
         * $fmt = $this->getFormat($tag);
         * $newData[] = chr($fmt >> 8);
         * $newData[] = chr($fmt);
         * 
         * //components
         * $chars = preg_split('//', $val, -1, PREG_SPLIT_NO_EMPTY);
         * $ByteCount = count($chars);
         * 
         * $Components = $ByteCount / $this->BytesPerFormat[$fmt];
         * 
         * $newData[] = chr($Components >> 32);
         * $newData[] = chr($Components >> 16);
         * $newData[] = chr($Components >> 8);
         * $newData[] = chr($Components);
         * 
         * 
         * //$newData = array_merge($newData,$chars);
         * 
         * $newData = array_merge($newData,$this->ImageInfo["ThumbnailPointer"]);
         * $totalLength += count($this->ImageInfo["ThumbnailPointer"]);
         * }
         */

        $totalLength += 2;
        $newData[0] = chr($totalLength >> 8);
        $newData[1] = chr($totalLength);

        $newData[16] = chr($totalElements >> 8);
        $newData[17] = chr($totalElements);

        $this->sections[$this->exifSection]["data"] = $newData;
        $this->sections[$this->exifSection]["size"] = $totalLength;
    } 

    /**
     * Searched for the tag specified in the sections list
     * 
     * @param hex $ Tag to search for
     * @return int -1 - Tag not found
     */
    function findMarker($marker)
    {
        for($i = 0;$i < $this->currSection;$i++) {
            if ($this->sections[$i]["type"] == $marker) {
                return $i;
            } 
        } 
        return -1;
    } 

    /**
     * Adds comment to the image.
     * NOTE: Will have to call writeExif for the comments and
     *          other data to be written back to image.
     * 
     * @param string $ Commnent as string
     */
    function addComment($comment)
    {
        /**
         * * check if comments already exists!
         */
        $commentSection = $this->findMarker(M_COM);
        if ($commentSection == -1) {
            // make 3rd element as comment section - Push-up all elements
            for($i = $this->currSection;$i > 2;$i--) {
                $this->sections[$i]["type"] = $this->sections[$i-1]["type"];
                $this->sections[$i]["data"] = $this->sections[$i-1]["data"];
                $this->sections[$i]["size"] = $this->sections[$i-1]["size"];
            } 
            $this->currSection++;
            $commentSection = 2;
        } 

        $data[0] = 0; // dummy data
        $data[1] = 0; // dummy data
        
        $chars = preg_split('//', $comment, -1, PREG_SPLIT_NO_EMPTY);
        $data = array_merge($data, $chars);

        $this->sections[$commentSection]["size"] = count($data);

        $data[0] = chr($this->sections[$commentSection]["size"] >> 8);
        $data[1] = chr($this->sections[$commentSection]["size"]);

        $this->sections[$commentSection]["type"] = M_COM;
        $this->sections[$commentSection]["data"] = $data;
    } 

    /**
     * Return the format of data of any tag.
     * 
     * @param hex $ Tag whose format has to looked for
     * @return int Return the format as int
     */
    function getFormat($tag)
    {
        global $FMT_BYTE_ARRAY, $FMT_STRING_ARRAY, $FMT_USHORT_ARRAY, $FMT_ULONG_ARRAY,
        $FMT_URATIONAL_ARRAY, $FMT_SBYTE_ARRAY, $FMT_UNDEFINED_ARRAY, $FMT_SSHORT_ARRAY,
        $FMT_SLONG_ARRAY, $FMT_SRATIONAL_ARRAY, $FMT_SINGLE_ARRAY, $FMT_DOUBLE_ARRAY;

        if (in_array($tag, $FMT_BYTE_ARRAY)) {
            return FMT_BYTE;
        } else if (in_array($tag, $FMT_STRING_ARRAY)) {
            return FMT_STRING;
        } else if (in_array($tag, $FMT_USHORT_ARRAY)) {
            return FMT_USHORT;
        } else if (in_array($tag, $FMT_ULONG_ARRAY)) {
            return FMT_ULONG;
        } else if (in_array($tag, $FMT_URATIONAL_ARRAY)) {
            return FMT_URATIONAL;
        } else if (in_array($tag, $FMT_SBYTE_ARRAY)) {
            return FMT_SBYTE;
        } else if (in_array($tag, $FMT_UNDEFINED_ARRAY)) {
            return FMT_UNDEFINED;
        } else if (in_array($tag, $FMT_SSHORT_ARRAY)) {
            return FMT_SSHORT;
        } else if (in_array($tag, $FMT_SRATIONAL_ARRAY)) {
            return FMT_SRATIONAL;
        } else if (in_array($tag, $FMT_SINGLE_ARRAY)) {
            return FMT_SINGLE;
        } else if (in_array($tag, $FMT_DOUBLE_ARRAY)) {
            return FMT_DOUBLE;
        } 
    } 

    /**
     * Returns the exif information stored
     */
    function getExif()
    {
        if ($this->exifSection > -1) {
            return $this->sections[$this->exifSection]["data"];
        } 
        /**
         * * Exif data does not exists
         */
        return -1;
    } 

    /**
     * Returns the exif information stored
     * 
     * @param string $ Exif Data to be added.
     * 
     * NOTE: This function will blindly replace any existing EXIF data
     */
    function addExif($exifData)
    {
        $exifSection = $this->findMarker(M_EXIF);
        if ($exifSection == -1) {
            // make 3rd element as comment section - Push-up all elements
            for($i = $this->currSection;$i > 2;$i--) {
                $this->sections[$i]["type"] = $this->sections[$i-1]["type"];
                $this->sections[$i]["data"] = $this->sections[$i-1]["data"];
                $this->sections[$i]["size"] = $this->sections[$i-1]["size"];
            } 
            $exifSection = 2;
            $this->currSection++;
        } 

        $this->sections[$exifSection]["type"] = M_EXIF;
        $this->sections[$exifSection]["data"] = $exifData;
        $this->sections[$exifSection]["size"] = strlen($exifData);
    } 

    /**
     * Write the whole image back into a file.
     *     This function does not write back to the same file.
     *     You need to specify a filename
     * 
     * @param string $ filename to save the JPEG content to
     */
    function writeImage($file)
    {
        $file = trim($file);
        if (empty($file)) {
            $this->errno = 3;
            $this->errstr = "File name not provided!";
            debug($this->errstr, 1);
        } 

        $fp = fopen($file, "wb");

        /**
         * * Initial static jpeg marker.
         */
        fwrite($fp, chr(0xff));
        fwrite($fp, chr(0xd8));

        if ($this->sections[0]["type"] != M_EXIF && $this->sections[0]["type"] != M_JFIF) {
            $JfifHead = array(
                chr(0xff), chr(M_JFIF),
                chr(0x00), chr(0x10), 'J' , 'F' , 'I' , 'F' , chr(0x00), chr(0x01),
                chr(0x01), chr(0x01), chr(0x01), chr(0x2C), chr(0x01), chr(0x2C), chr(0x00), chr(0x00)
                );

            fwrite($fp, implode("", $JfifHead));
        } 

        /**
         * * write each section back into the file
         */
        for($key = 0;$key < $this->currSection-1;$key++) {
            if (!empty($this->sections[$key]["data"])) {
                fwrite($fp, chr(0xff));
                fwrite($fp, chr($this->sections[$key]["type"]));
                /**
                 * dat acan be array as well as string. Check the data-type of data.
                 * If it is an array then convert it to string.
                 */
                if (is_array($this->sections[$key]["data"])) {
                    $this->sections[$key]["data"] = implode("", $this->sections[$key]["data"]);
                } 
                fwrite($fp, $this->sections[$key]["data"]);
            } 
        } 
        // Write the remaining image data.
        if (is_array($this->sections[$key]["data"])) {
            $this->sections[$key]["data"] = implode("", $this->sections[$key]["data"]);
        } 
        fwrite($fp, $this->sections[$key]["data"]);
        fclose($fp);
    } 
} // end of class

?>
