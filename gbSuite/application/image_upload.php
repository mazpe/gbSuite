<?php

	/*
	 * This is the script to upload images, and risize it.
	 *   
	 */

    class ImageUploader extends FileUploader
    {
    	
    	
    	var $ratio;
    	var $imageType;
    	var $width;
    	var $height;
    	
    	/*
    	 * @param dest_dir	 : The destination directory of the uploader image 	
    	 * @param name		 : The new name of the uploader image.
    	 * @param $image	 :	The uploaded image.
    	 */
    	    	
    	public function __construct($dest_dir,$image,$name)
    	{
    		$this->ratio = true;
    		parent::__construct($dest_dir,$image,$name);
    			
    	}
    	
    	public function setRatio($ratio)
    	{
    		$this->ratio = $ratio;
    	}
    	
    	public function setDestDir($dest_dir)
    	{
    		$this->dest_dir = $dest_dir;
    	}
    	
    	public function setName($dest_dir)
    	{
    		$this->dest_dir = $dest_dir;
    	}
    	
    	/*
    	 * Try to adjust the size of the image in this dimension
    	 */
    	public function setSize($width, $height)
    	{
    		$this->width = $width;
    		$this->height = $height;
    	} 
    	
    	/*
    	 * @param width		: the new width of the new image.
    	 * @param heigth	: the new width of the new image.
    	 * 		If the width or the height are 0 then the original property is used.
    	 * @return :	true if all is done correctly or an array if an error ocurred;  
    	 */
    	 
    	function move_image($width, $height)
    	{
    		
    		$this->setSize($width, $height);
    		
    		if(parent::move())
    		{
    			$this->resize();
    		}
    		else
    		{
    			echo "Error moving image";
    		}
    		
    		
    		return true;		
    	}
    	
    	function resize()
    	{
    		
    		$image = $this->open_image($this->uploaded_image);    		    		   		    		
    		$actualSize = array('width'=> imagesx($image) ,'height'=> imagesy($image) );
    		
    		$newSize = $this->getNewSize($actualSize);
   		
			$image_resized = imagecreatetruecolor($newSize['width'], $newSize['height']);
			
			imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $newSize['width'], $newSize['height'], $actualSize['width'], $actualSize['height']);
			$this->saveImage ($image_resized,$this->uploaded_image);
						    		
    		return true;
    	}
    	
    	public function getNewSize($imageSize)
    	{
    		if($this->ratio)
    		{
				$width = 0;
				$height = 0;

				if($imageSize['width'] > $imageSize['height'])
				{
					$factor = $this->width / $imageSize['width'] ;	
				}
				else
				{
					$factor =  $this->height / $imageSize['height'];
				}
				
				return array('width'=>$imageSize['width'] * $factor,
							 'height'=>$imageSize['height'] * $factor);
    		}
    		else
    		{
    			return array("width"=>$this->width,
							'height'=>$this->height);
    		}
    	}
    	
    	function saveImage($image,$fileName)
    	{
    		$function = "image".$this->imageType;
    		$function($image,$fileName);
    	}
    	
    	/*
    	 * Move the image with the configured values
    	 */
    	public function move()
    	{
			move_image(0, 0);
    	}
    	
    	
    	function open_image ($file) 
    	{
        # JPEG:
        $im = @imagecreatefromjpeg($file);
        if ($im !== false) { $this->imageType = "jpeg"; return $im; }

        # GIF:
        $im = @imagecreatefromgif($file);
        if ($im !== false) { $this->imageType = "gif"; return $im; }

        # PNG:
        $im = @imagecreatefrompng($file);
        if ($im !== false) {  $this->imageType = "png"; return $im; }

        # GD File:
        $im = @imagecreatefromgd($file);
        if ($im !== false) {  $this->imageType = "gd"; return $im; }

        # GD2 File:
        $im = @imagecreatefromgd2($file);
        if ($im !== false) {  $this->imageType = "gd2"; return $im; }

        # WBMP:
        $im = @imagecreatefromwbmp($file);
        if ($im !== false) {  $this->imageType = "wbmp"; return $im; }

        # XBM:
        $im = @imagecreatefromxbm($file);
        if ($im !== false) {  $this->imageType = "xbm"; return $im; }

        # XPM:
        $im = @imagecreatefromxpm($file);
        if ($im !== false) {  $this->imageType = "xpm"; return $im; }

        # Try and load from string:
        $im = @imagecreatefromstring(file_get_contents($file));
        if ($im !== false) 
        { 
        	 $this->imageType = "string"; 
        	return $im; 
        }

        return false;
		}
   }
    
    
    class FileUploader
    {
  		var $dest_dir; //destination directory of the uploaded image
    	var $name;		// The name of the new image
    	var $image;		// the name of uploaded image.
    	
    	var $uploaded_image;
    	
    	public function __construct($dest_dir,$image,$name)
    	{
    		$this->dest_dir = $dest_dir;
    		$this->image = $image;
    		$this->name = $name;
    	}
    	
    	public function move()
    	{
    		try
    		{
				$file = $_FILES[$this->image];
				$tmp = explode(".",$file['name']);
				
				if(count($tmp) >= 2 )
				{
					$ext =  ".".$tmp[count($tmp)-1];
				}
				else
				{
					$ext = "";
				}
				
				
				copy($file[tmp_name],$this->dest_dir."/".$this->name."$ext");
				
				$this->uploaded_image = $this->dest_dir."/".$this->name."$ext";
				
				return true;
    		}
    		catch(Exception $e)
    		{
    			return false;	
    		}
   		} 	
    }
    
?>

