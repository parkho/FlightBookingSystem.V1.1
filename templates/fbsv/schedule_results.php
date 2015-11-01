<script src="http://skyvector.com/linkchart.js"></script>
<?php
$pilotid = Auth::$userinfo->pilotid;
$last_location = FBSVData::get_pilot_location($pilotid, 1);
$last_name = OperationsData::getAirportInfo($last_location->arricao);
?>
<h3>Flight Dispatch</h3>
<ul>
	<li>Available flights from: <b><font color="#FF3300"><?php echo $last_location->arricao.' ( '.$last_name->name.')' ;?></font></b></li>
</ul>
<table>
<?php
if(!$allroutes)
{
?>
	<tr><td align="center">No flights from <?php echo $last_location->arricao.' ( '.$last_name->name.')' ;?>!</td></tr>
	
<?php
}
else
{
?>


<thead>
<tr>
    <th>Flight ID</th>
    <th>Origin</th>
    <th>Destination</th>
    <th>Aircraft</th>
    <th>Options</th>
</tr>
</thead>
<?php
foreach($allroutes as $route)
{
	if($last_location->arricao != $route->depicao)
	{
		continue;
	}
	if(Config::Get('RESTRICT_AIRCRAFT_RANKS') === true && Auth::LoggedIn())
	{
		$s="SELECT * FROM phpvms_aircraft WHERE name='$route->aircraft'";
		$ss=DB::get_row($s);
		if($ss->minrank > Auth::$userinfo->ranklevel)
		{
			continue;
		}
	}
?>

<tr>
	<td><?php echo $route->code . $route->flightnum?></td>
	<td><?php echo $route->depicao ;?></td>
	<td><?php echo $route->arricao ;?></td>
	<td><?php echo $route->aircraft ;?></td>
    <td><input type="button" onclick="$('#details_dialog_<?php echo $route->flightnum;?>').toggle('slow')" Value="Briefing" title="Click To View Flight Briefing!">
         
	   <?php 
		$bids = SchedulesData::getBids(Auth::$pilot->pilotid);
		if (count($bids) > 0)
		{
			?>
			 <input type="button" disabled="disabled" value="Reserved" title="You Have A Reservation!">
			 <a href="<?php echo url('/schedules/bids'); ?>"><input type="submit" name="submit" value="Remove Bid" /></a>
			<?php
		}
		elseif($route->bidid != 0)
	    {
        ?>
        <input type="button" disabled="disabled" value="booked" title="Flight Is Already Booked!">
        <?php
		}
        else
		{
		?>
	    <a id="<?php echo $route->id; ?>" style="text decoration: none;" href="<?php echo url('/schedules/addbid?id='.$route->id);?>"><input type="button" value="Book Flight" title="Click To Book Flight!"></a>
        <?php                    
        }
        ?>
    </td>
</tr>

        <td colspan="5">
		
		<table id="details_dialog_<?php echo $route->flightnum;?>" style="display:none">
			<thead>
			<tr>
			<th colspan="4">&nbsp;</th>
			</tr>
			<tr>
			<th style="text-align: center;" bgcolor="#00405e" colspan="4"><font color="white">Flight Brefing</font></th>
			</tr>
			
			<tr>
			<td align="left">Deaprture:</td>
			<td colspan="0" align="left" ><b><?php
			$name = OperationsData::getAirportInfo($route->depicao);
			echo "{$name->name}"?></b></td>
			<td align="left">Arrival:</td>
			<td colspan="0" align="left" ><b><?php 
			$name = OperationsData::getAirportInfo($route->arricao);
			echo "{$name->name}"?></b></td>
			</tr>
			<tr>
			<td align="left">Aircraft</td>
			<td colspan="0" align="left" ><b><?php 
			$plane = OperationsData::getAircraftByName($route->aircraft);
			echo $plane->fullname ; ?></b></td>
			<td align="left">Distance:</td>
			<td colspan="0" align="left" ><b><?php echo $route->distance . Config::Get('UNITS') ;?></b></td>
			</tr>
			<tr>
			<td align="left">Dep Time:</td>
			<td colspan="0" align="left" ><b><font color="red"><?php echo $route->deptime?> GMT</font></b></td>
			<td align="left">Arr Time:</td>
			<td colspan="0" align="left" ><b><font color="red"><?php echo $route->arrtime?> GMT</font></b></td>
			</tr>
			<tr>
			<td align="left">Altitude:</td>
			<td colspan="0" align="left" ><b><?php echo $route->flightlevel; ?> ft</b></td>
			<td align="left">Duration:</td>
			<td colspan="0" align="left" ><font color="red"><b>
			<?php 
			
			$dist = $route->distance;
			$speed = 440;
			$app = $speed / 60 ;
			$flttime = round($dist / $app,0)+ 20;
			$hours = intval($flttime / 60);
            $minutes = (($flttime / 60) - $hours) * 60;
			if($hours > "9" AND $minutes > "9")
			{
			echo $hours.':'.$minutes ;
			}
			else
			{
			echo '0'.$hours.':'.$minutes ;
			}
			?> Hrs</b></font></td>
			</tr>
			<tr>
			<td align="left">Days</td>
			<td colspan="0" align="left" ><b><?php echo Util::GetDaysLong($route->daysofweek) ;?></b></td>
			<td align="left">Price:</td>
			<td colspan="0" align="left" ><b><?php echo $route->price ;?>.00</b></td>
			</tr>
			<tr>
			<td align="left">Flight Type:</td>
			<td colspan="0" align="left" ><b><?php
			if($route->flighttype == "P")
			{
			echo'Passenger' ;
			}
			if($route->flighttype == "C")
			{
			echo'Cargo' ;
			}
			if($route->flighttype == "H")
			{
			echo'Charter' ;
			}
			?></b></td>
			<td align="left">Flown</td>
			<td colspan="0" align="left" ><b><?php echo $route->timesflown ;?></b></td>
			</tr>
			<tr><td>Route:</td><td colspan="3" align="left" ><b><?php echo $route->route ;?></b></td>
			</tr>
			
			<tr>
			<th style="text-align: center;" bgcolor="#00405e" colspan="4"><font color="white">Fuel Calculation for <?php 
			$plane = OperationsData::getAircraftByName($route->aircraft);
			echo $plane->fullname ; ?>
			</font></th>
			</tr>
			<?php
			$aircraft = $plane->name;
			$distance = $route->distance;
			$param = FCalculator::getparamaircraft($aircraft);
			$fuel_hour = $param->hour;
			$fuel_reserve = $param->hour * 3/4;
			$fuel_flow = $param->flow;
			$speed = $param->speed;
			$fuel_taxi = 200;
            $total = FCalculator::calculatefuel($aircraft, $distance);      
			?> 	
						 <tr>
			             <td align="left" colspan="2">Average Cruise Speed:</td>
						 <td align="left" colspan="2"><b><?php echo $speed;?> kt/h - 800 km/h</b></td>
						 </tr>
						 <tr>
			             <td align="left" colspan="2">Fuel Per 1 Hour:</td>
						 <td align="left" colspan="2"><b><?php	echo $fuel_hour ;?> kg - <?php echo ($fuel_hour *2.2) ;?> lbs</b></td>
						 </tr>
						 <tr>
						 <td align="left" colspan="2">Fuel Per 100 NM:</td>
						 <td align="left" colspan="2"><b><?php echo $fuel_flow ;?> kg - <?php echo ($fuel_flow *2.2) ;?> lbs</b></td>
						 </tr>
						 <tr>
						 <td align="left" colspan="2">Taxi Fuel:</td>
						 <td align="left" colspan="2"><b><?php echo $fuel_taxi ;?> kg - <?php echo ($fuel_taxi *2.2) ;?> lbs</b></td>
						 <tr>
						 <td align="left" colspan="2">Minimum Fuel Requiered At Destination:</td>
						 <td align="left" colspan="2"><b><?php echo $fuel_reserve ;?> kg - <?php echo ($fuel_reserve *2.2) ;?> lbs</b></td>
						 </tr>
						 <tr>
						 <td align="center" colspan="4"><font color="blue" size="4">Total Estimated Fuel Requiered For This Route:&nbsp;&nbsp;&nbsp;<?php echo round($total, 1) ;?> kg - <?php echo round(($total *2.2), 1) ;?> lbs</font></td>
						 </tr>
                         <tr>
						 <td align="center" colspan="4"><font size="3" color="red"><b>TO PREVENT ANY MISCALCULATION ADD 500 KG EXTRA!</b></font></td>                                  
                         </tr>
			</td>
			</tr>
			
			<tr>
			<th style="text-align: center;" bgcolor="#00405e" colspan="4"><font color="white">Flight Map</font></th>
			</tr>
			
			<tr>
			<td width="100%" colspan="4">
			<?php
			$string = "";
                        $string = $string.$route->depicao.'+-+'.$route->arricao.',+';
                        ?>

                        <img width="100%" src="http://www.gcmap.com/map?P=<?php echo $string ?>&amp;MS=bm&amp;MR=240&amp;MX=680x200&amp;PM=pemr:diamond7:red%2b%22%25I%22:red&amp;PC=%230000ff" />
			</tr>
			</td>
			</thead>
		 </table>	
        </td>
</tr>
			
<?php
}
}
?>
</table>

			
