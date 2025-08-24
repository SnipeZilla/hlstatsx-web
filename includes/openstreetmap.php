<?php
/*
HLstatsX Community Edition - Real-time player and clan rankings and statistics
Copyleft (L) 2008-20XX Nicholas Hastings (nshastings@gmail.com)
http://www.hlxcommunity.com

HLstatsX Community Edition is a continuation of 
ELstatsNEO - Real-time player and clan rankings and statistics
Copyleft (L) 2008-20XX Malte Bayer (steam@neo-soft.org)
http://ovrsized.neo-soft.org/

ELstatsNEO is an very improved & enhanced - so called Ultra-Humongus Edition of HLstatsX
HLstatsX - Real-time player and clan rankings and statistics for Half-Life 2
http://www.hlstatsx.com/
Copyright (C) 2005-2007 Tobias Oetzel (Tobi@hlstatsx.com)

HLstatsX is an enhanced version of HLstats made by Simon Garner
HLstats - Real-time player and clan rankings and statistics for Half-Life
http://sourceforge.net/projects/hlstats/
Copyright (C) 2001  Simon Garner
            
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

For support and installation notes visit http://www.hlxcommunity.com
*/
global $db, $game, $g_options, $clandata, $clan;
?> 
<script type="text/javascript">

    OpenMap = L.map('map',{zoomControl:false}).setView([47.45, -12.00], 3);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        minZoom: 2,
        maxZoom: 19,
        attribution: '&copy; <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a>'
    }).addTo(OpenMap);
    var bounds = L.latLngBounds(L.latLng(-89.98155760646617, -180), L.latLng(89.99346179538875, 180));
    OpenMap.setMaxBounds(bounds);
    OpenMap.on('drag', function() {
        OpenMap.panInsideBounds(bounds, { animate: false });
    });
    const imagePath = "<?= IMAGE_PATH ?>";
    const game = "<?= $game ?>";
    const LeafIcon = L.Icon.extend({ options: {
                        shadowUrl: imagePath+"/marker-shadow.png",
                        iconSize:      [25,41],
                        iconAnchor:    [12,41],
                        popupAnchor:   [1,-34],
                        tooltipAnchor: [16,-28],
                        shadowSize:    [41,41] }
                   });
     function createServer(lat,lng,servers, city, country,kills) {
         var s_icon = new LeafIcon({iconUrl: imagePath+"/server-marker.png"});
         var card='<div><span class="openmap-city">'+city+'</span>, <span class="openmap-country">'+country+'</span></div>';
         for ( var i=0; i<servers.length; i++) {
             card+='<div><span class="openmap-name">'+servers[i][2].replace(/\\/g, "")+'</span></div>'+
             '<div>&nbsp;&nbsp;Click to join: <a href="steam://connect/' + servers[i][1] + '">'+servers[i][1]+'</a></div>';
             
         }
         var marker=new L.marker([lat, lng],{icon: s_icon}).bindPopup(card).addTo(OpenMap);
                    marker._icon.classList.add('server');
     }
     function createPlayer(lat,lng,state, country, players) {console.log(players)
         var s_icon = new LeafIcon({iconUrl: imagePath+"/player-marker.png"});
         var card='<div><span class="openmap-city">'+state+'</span>, <span class="openmap-country">'+country+'</span></div>';
         for ( var i=0; i<players.length; i++) {
             card+='<div><a class="openmap-name" href="hlstats.php?mode=playerinfo&amp;player='+players[i][0]+'">'+players[i][1].replace(/\\/g, "")+'</a><span> - '+players[i][4]+'</div>';
         }
         var marker=new L.marker([lat, lng],{icon: s_icon}).bindPopup(card).addTo(OpenMap);
                    marker._icon.classList.add('server');
     }

</script>
<?php
// Servers
$db->query("SELECT serverId, IF(publicaddress != '', publicaddress, CONCAT(address, ':', port)) AS addr, name, kills, lat, lng, city, country FROM hlstats_Servers WHERE game='$game' AND lat IS NOT NULL AND lng IS NOT NULL");

$servers = array();
while ($row = $db->fetch_array())
{
//Skip this part, if we already have the location info (should be the same)
if (!isset($servers[$row['lat'] . ',' . $row['lng']]))
{
$servers[$row['lat'] . ',' . $row['lng']] = array('lat' => $row['lat'], 'lng' => $row['lng'], 'addr' => $row['addr'], 'city' => $row['city'], 'country' => $row['country']);
}

$servers[$row['lat'] . ',' . $row['lng']]['servers'][] = array('serverId' => $row['serverId'], 'addr' => $row['addr'], 'name' => $row['name'], 'kills' => $row['kills']);
}
foreach ($servers as $map_location)
{
    $kills = 0;
    $servers_js = array();
    foreach ($map_location['servers'] as $server)
    {
        $temp = "[" . $server['serverId'] . ',';
        $temp .= "'" . $server['addr'] . '\',';
        $temp .= "'" . $server['name'] . '\']';
        $servers_js[] = $temp;
        $kills += $server['kills'];
    }
    echo "<script>createServer(" . $map_location['lat'] . ', '
                          . $map_location['lng'] . ', '
                          . '["'.implode(',', $servers_js) . '"], ' 
                          . '"'. $map_location['city'].'", '
                          . '"'.$map_location['country'].'", '
                          . $kills . ');</script><br/>';

}

$db->query("SELECT 
                hlstats_Livestats.* 
            FROM 
                hlstats_Livestats
            INNER JOIN    
                hlstats_Servers 
            ON (hlstats_Servers.serverId=hlstats_Livestats.server_id)
            WHERE 
                hlstats_Livestats.cli_lat IS NOT NULL 
            AND hlstats_Livestats.cli_lng IS NOT NULL
            AND hlstats_Servers.game='$game'
            ORDER by hlstats_Livestats.team
           ");
$players = array();
while ($row = $db->fetch_array())
{
//Skip this part, if we already have the location info (should be the same)
if (!isset($players[$row['cli_lat'] . ',' . $row['cli_lng']]))
{
    $players[$row['cli_lat'] . ',' . $row['cli_lng']] = array('cli_lat' => $row['cli_lat'], 'cli_lng' => $row['cli_lng'], 'cli_state' => $row['cli_state'], 'cli_country' => $row['cli_country']);
}

$players[$row['cli_lat'] . ',' . $row['cli_lng']]['players'][] = array('playerId' => $row['player_id'], 'name' => $row['name'], 'kills' => $row['kills'], 'deaths' => $row['deaths'], 'connected' => $row['connected']);
}

foreach ($players as $map_location)
{
    $kills = 0;
    $players_js = array();
    foreach ($map_location['players'] as $player)
    {
        $stamp = time() - $player['connected'];
        $hours = sprintf("%02d", floor($stamp / 3600));
        $min = sprintf("%02d", floor(($stamp % 3600) / 60));
        $sec = sprintf("%02d", floor($stamp % 60));
        $time_str = $hours . ":" . $min . ":" . $sec;

        $temp = "[" . $player['playerId'] . ',';
        $temp .= "'" . $player['name'] . "',";
        $temp .= $player['kills'] . ',';
        $temp .= $player['deaths'] . ',';
        $temp .= "'" . $time_str . "']";
        $players_js[] = $temp;
    }
    echo "<script>createPlayer(" . $map_location['cli_lat'] . ', '
                                 . $map_location['cli_lng'] . ', '
                                 . '"'. $map_location['cli_state'] .'", '
                                 . '"'. $map_location['cli_country'] .'", '
                                 . '["'.implode(',', $players_js) . '"] );</script><br/>';


}
?>
