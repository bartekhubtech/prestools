<?php
// INTERSEPTING REQUEST FOR IMAGE
if (isset($_REQUEST["img"])) {


    // FUNCTION MUST BE DESCRIBED BEFORE IT'S CALL
    function draw($params=array()) {
        // check that we have enough params to draw graph
        if ( empty($params)
            || ( empty($params["canvas"]) && (empty($params["width"]) || empty($params["height"])) )
            || empty($params["periods"]) || empty($params["data"]) || empty($params["colors"]) ) {
            printf("Not enough input parameters for drawing:\n");
            print_r($params);
            return;
        }

        $font = empty($params["font"]) ? './Roboto-Light.ttf' : $params["font"];
        $font_size = empty($params["font-size"]) ? 10 : $params["font-size"];

        // graph canvas
        if (empty($params["canvas"])) {
            $canvas_width = $params["width"];
            $canvas_height = $params["height"];
            $canvas = @imagecreatetruecolor($canvas_width, $canvas_height) or die("Unable to create image canvas.");
        } else {
            $canvas = $params["canvas"];
            $canvas_width = imagesx($canvas);
            $canvas_height = imagesy($canvas);
        }

        // graph draw area (where grid and graph placed)
        $draw_area_multiplier = 0.8; // by default wi will draw graph in 80% area of canvas
        $draw_width = round($canvas_width*$draw_area_multiplier);
        $draw_height = round($canvas_height*$draw_area_multiplier);
        // zero point for GD
        $draw_x0 = round(($canvas_width-$draw_width)/2);
        $draw_y0 = round(($canvas_height-$draw_height)/2);
        // zero point for humans
        $human_x0 = $draw_x0;
        //$human_y0 = $draw_y0 + $draw_height; // will be calculated below

        // standard colors
        $color_black = imagecolorallocate($canvas,0,0,0);
        $color_white = imagecolorallocate($canvas,255,255,255);
        $color_grey = imagecolorallocate($canvas,127,127,127); // used for grid lines

        // setting canvas background, default:white
        if (is_array($params["background-color"]) && count($params["background-color"]) > 2) {
            $a = $params["background-color"];
            $bg_color = imagecolorallocate($canvas, $a[0], $a[1], $a[2]);
            imagefill($canvas, 0, 0, $bg_color);
        } else {
            imagefill($canvas, 0, 0, $color_white);
        }

        // choosing grid color. default: grey (127,127,127)
        if (is_array($params["grid-color"]) && count($params["grid-color"]) > 2) {
            $a = $params["grid-color"];
            $grid_color = imagecolorallocate($canvas, $a[0], $a[1], $a[2]);
        }

        $data = $params["data"];

        /*
         * vertical lines for grid
         */
        // calculating max available grids in timeline
        $period_count = 0;
        foreach ($data as $key=>$value)
            $period_count = max($period_count, count($value));
        if ($period_count < 1) die("No data found for periods.");
        // we have param $periods with period labels and need to sure that the count of periods in data is the same as in $periods
        $periods = $params["periods"];
        if (count($periods) < $period_count) printf("There are not enough labels for data periods.");
        // now we have periods counted and can create vertical lines for them
        $period_width = round($draw_width/($period_count+0.4)); //+0.4 gives us 0.2width left and 0.2width right place for horizontal lines
        $human_x0 = $draw_x0 + $period_width*0.2; // x of first (zero) point
        for ($i=0; $i < $period_count; $i++) {
            $x = $human_x0+$period_width*$i;
            $y0 = $draw_y0; //top
            $y = $draw_y0+$draw_height; //bottom
            imageline($canvas, $x ,$y0, $x, $y, $grid_color);

            $bbox = imagettfbbox($font_size, 0, $font, $periods[$i]);
            $xt = $x - ($bbox[2]-$bbox[0])/3; $yt = $y + 1.5*$font_size + ($bbox[3]-$bbox[5])/3;
            imagettftext($canvas,$font_size,0, $xt, $yt, $grid_color, $font, $periods[$i]);
        }

        /*
         * horizontal lines for grid
         */
        // discovering max & min Y data values
        $labels = array_keys($data);
        $max_value = $data[$labels[0]][0]; // setting to the first element
        $min_value = $data[$labels[0]][0]; // too
        foreach ($data as $key=>$val) {
            $max_value = max($max_value, max($val));
            $min_value = min($min_value, min($val));
        }

        $graph_high_value = ceil($max_value/10)*10;
        $graph_low_value = floor($min_value/10)*10;
        $graph_range = $graph_high_value - $graph_low_value;
        $support_values = array(
            $graph_high_value,
            $graph_high_value - 0.25*$graph_range,
            $graph_high_value - 0.5*$graph_range,
            $graph_high_value - 0.75*$graph_range,
            $graph_low_value
        );

        $xl_s = $draw_x0; $xl_e = $draw_x0 + $draw_width - $period_width*1.1;
        $support_height = ($draw_height-$draw_height/4*0.2);
        $yl = $draw_y0 + $draw_height/4*0.1;

        imageline($canvas, $xl_s ,$yl, $xl_e, $yl, $grid_color);
        $bbox = imagettfbbox($font_size, 0, $font, $support_values[0]);
        $xt = $draw_x0-5 - ($bbox[2]-$bbox[0]); $yt = $yl + ($bbox[3]-$bbox[5])/3;
        imagettftext($canvas,$font_size,0, $xt, $yt, $grid_color, $font, $support_values[0]);

        imageline($canvas, $xl_s ,$yl+0.25*$support_height, $xl_e, $yl+0.25*$support_height, $grid_color);
        $bbox = imagettfbbox($font_size, 0, $font, $support_values[1]);
        $xt = $draw_x0-5 - ($bbox[2]-$bbox[0]); $yt = $yl + 0.25*$support_height + ($bbox[3]-$bbox[5])/3;
        imagettftext($canvas,$font_size,0, $xt, $yt, $grid_color, $font, $support_values[1]);

        imageline($canvas, $xl_s ,$yl+0.5*$support_height, $xl_e, $yl+0.5*$support_height, $grid_color);
        $bbox = imagettfbbox($font_size, 0, $font, $support_values[2]);
        $xt = $draw_x0-5 - ($bbox[2]-$bbox[0]); $yt = $yl + 0.5*$support_height + ($bbox[3]-$bbox[5])/3;
        imagettftext($canvas,$font_size,0, $xt, $yt, $grid_color, $font, $support_values[2]);

        imageline($canvas, $xl_s ,$yl+0.75*$support_height, $xl_e, $yl+0.75*$support_height, $grid_color);
        $bbox = imagettfbbox($font_size, 0, $font, $support_values[3]);
        $xt = $draw_x0-5 - ($bbox[2]-$bbox[0]); $yt = $yl + 0.75*$support_height + ($bbox[3]-$bbox[5])/3;
        imagettftext($canvas,$font_size,0, $xt, $yt, $grid_color, $font, $support_values[3]);

        imageline($canvas, $xl_s ,$yl+$support_height, $xl_e, $yl+$support_height, $grid_color);
        $bbox = imagettfbbox($font_size, 0, $font, $support_values[4]);
        $xt = $draw_x0-5 - ($bbox[2]-$bbox[0]); $yt = $yl + $support_height + ($bbox[3]-$bbox[5])/3;
        imagettftext($canvas,$font_size,0, $xt, $yt, $grid_color, $font, $support_values[4]);

        $human_y0 = $yl+$support_height; // y of (zero) line

        // vertical scaling
        $value_range = $graph_range;
        $value_per_point =  ($draw_height - $draw_height/4*0.2) / $value_range;
//    printf("Value per point: %s\n",$value_per_point);

        // normalizing values
        $draw_values = $data;
        foreach ($draw_values as $value)
            foreach ($value as $val)
                $val -= $min_value;

        // plotting main graph
        $colors = array();
        foreach ($params["colors"] as $value) {
            $colors[] = imagecolorallocate($canvas,$value[0],$value[1],$value[2]);
        }
        imagesetthickness($canvas,3);

        $color_index = 0;
        foreach ($data as $label=>$values) {
            $line_color = $colors[$color_index];
            foreach ($values as $i=>$val) {
                if ($i==0) {
                    $start_x = $human_x0;
                    $start_y = $human_y0 - ($val-$graph_low_value)*$value_per_point;
                    //               printf("Start (%s,%s) val %s\n",$start_x,$start_y,$val);
                    continue;
                }
                $end_x = $start_x+$period_width;
                $end_y = $human_y0 - ($val-$graph_low_value)*$value_per_point;
                //           printf("End (%s,%s) val %s\n",$end_x,$end_y,$val);
                imageline($canvas,$start_x,$start_y,$end_x,$end_y,$line_color);
                $start_x = $end_x;
                $start_y = $end_y;
            }
            $color_index++;
        }

        // legend and units
        $text_center = $canvas_width - ($canvas_width - $draw_width) / 4 -$period_width/2;
        $units_label_height = 0;
        if (!empty($params["units"])) {
            $text = "Units:\n".$params["units"];
            $bbox = imagettfbbox($font_size, 0, $font, $text);
            $text_height = ($bbox[1]-$bbox[7]) * 1.2;
            $units_label_height = $text_height;
            $line_height = $text_height / 2;
            $text_top = ($canvas_height - $draw_height) / 2 + $line_height; //$canvas_height / 4 - $text_height;
            $ar = explode("\n",$text);
            foreach ($ar as $i=>$val) {
                $bbox = imagettfbbox($font_size, 0, $font, $val);
                $left = $text_center - ($bbox[2]-$bbox[0]) / 2;
                imagettftext($canvas,$font_size,0, $left, $text_top+$i*$line_height, $grid_color, $font, $val);
            }
        }
        $font = empty($params["font-legend"]) ? $font : $params["font-legend"];
        $text = implode("\n",$labels);
        $bbox = imagettfbbox($font_size, 0, $font, $text);
        $text_height = ($bbox[1]-$bbox[7]) * 1.2;
        $line_height = $text_height / count($labels);
        $text_top =  ($canvas_height - $text_height / 2) / 2 + $units_label_height / 2;
        $ar = $labels;
        foreach ($ar as $i=>$val) {
            $bbox = imagettfbbox($font_size, 0, $font, $val);
            $left = $text_center - ($bbox[2]-$bbox[0]) / 2;
            imagettftext($canvas,$font_size,0, $left, $text_top+$i*$line_height, $colors[$i], $font, $val);
        }

        // showing result
        if (empty($params["canvas"])) {
            if (empty($params["save-to"])) {
                header('Content-type: image/png');
                imagepng($canvas);
            } else {
                imagepng($canvas, $params["save-to"]);
            }
            imagedestroy($canvas);
        }
    }

    $periods = array("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec");

    $data = array("2015"=> array("21", "23","22","22","30","35","31","40","28","33","36","37"),
        "2016"=> array("31", "33","32","32","36","37","39","45","34","33","51","33"),
        "2017"=> array("28", "43"));

    $colors = array(
        "red"   => array(200,0,0),
        "green" => array(0,200,0),
        "blue"  => array(0,0,200)
    );

    // "CANVAS" MUST BE ABSENT
    // "SAVE-TO" MUST BE ABSENT
    draw(array(
        "width"=>640, // no need if $canvas set
        "height"=>320, // no need if $canvas set
        "data"=>$data,
        "periods"=>$periods,
        "colors"=>$colors,
        "font"=>"./OpenSans.ttf",
        "font-size"=>11,
        "units"=>"1k items",
        "font-legend"=>"./OpenSans.ttf",
        "background-color"=> array(100,100,255), // RGB format for colors
        "grid-color"=> array(0,0,0)
    ));

    die(); // job done, img sent

}

// NEXT GO STANDARD PAGE EXCEPT <IMG TAG WICH SHOWS GRAPH
if(!@include 'approve.php') die( "approve.php was not found!");
// APPROVE.PHP CAN BE AT TOP IF IT WILL NOT SEND ANY HEADERS OR TEXT TO CLIENT WINDOW

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Order Modify</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
body {font-family:arial; font-size:13px}
form {width:260px;}
label,span {height:20px; padding:5px 0; line-height:20px;}
label {width:130px; display:block; float:left; clear:left}
label[for="costumer_id"] {float:left; clear:left}
span {float:left; clear:right}
input {border:1px solid #CCC}
input[type="text"] {width:120px; height:24px; margin:3px 0; float:left; clear:right; padding:0 0 0 2px; border-radius:3px; background:#F9F9F9}
	input[type="text"]:focus {background:#FFF}
select {width:120px; border:1px solid #CCC}
input[type="submit"] {clear:both; display:block; color:#FFF; background:#000; border:none; height:24px; padding:2px 4px; cursor:pointer; border-radius:3px}
input[type="submit"]:hover {background:#333}
</style>
<script type="text/javascript">
function check_products()
{ if(!checkPrices()) return false;
  productsform.verbose.value = orderform.verbose.checked;	
}

</script>
<script type="text/javascript" src="utils8.js"></script>
</head>
<body>
<?php print_menubar();

echo "Hello World";

?>
<!-- HERE WE CALL THE SAME SCRIPT WITH IMG PARAMETER TO INDICATE WE WANT SEE IMAGE -->
<img src="order-graph.php?img" alt="Test Graph"/>

<?php

include "footer1.php";
echo '</body></html>';


