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
    const LeafIcon = L.Icon.extend({ options: {
                        shadowUrl: imagePath+"/marker-shadow.png",
                        iconSize:      [25,41],
                        iconAnchor:    [12,41],
                        popupAnchor:   [1,-34],
                        tooltipAnchor: [16,-28],
                        shadowSize:    [41,41] }
                   });

    var markers = L.markerClusterGroup({
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: true,
        zoomToBoundsOnClick: true,
        maxClusterRadius: 20
    });
    function createServer(servers) {
        servers.forEach(server => {
            const s_icon = new LeafIcon({ iconUrl: imagePath + "/server-marker.png" });
    
            const card = `
                <div><span class="openmap-name">${server.name}</span></div>
                <div><span class="openmap-city">${server.city}</span>, <span class="openmap-country">${server.country}</span></div>
                <div>Click to join: 
                    <a class="openmap-addr" href="steam://connect/${server.addr}">
                        ${server.addr.replace(/\\/g, "")}
                    </a>
                </div>
            `;

            const marker = new L.marker([server.lat, server.lng], { icon: s_icon }).bindPopup(card)


            markers.addLayer(marker);
        });
    }

     function createPlayer(players) {
       players.forEach(player => {
         const s_icon = new LeafIcon({ iconUrl: imagePath + "/player-marker.png" });
         const card = `
           <div><span class="openmap-city">${player.cli_city}</span>, 
                <span class="openmap-country">${player.cli_country}</span></div>
           <div>
             <a class="openmap-name" href="hlstats.php?mode=playerinfo&player=${player.playerId}">
               ${player.name.replace(/\\/g, "")}
             </a>
             <span> - ${player.connected}</span>
           </div>
         `;
     
         const marker = L.marker([player.cli_lat, player.cli_lng], { icon: s_icon }).bindPopup(card);
         markers.addLayer(marker);
       });

       OpenMap.addLayer(markers);
}



</script>
<?php

if ( $type ==  "main" ) {
    // Servers
    $db->query("SELECT serverId, IF(publicaddress != '', publicaddress, CONCAT(address, ':', port)) AS addr, name, kills, lat, lng, city, country FROM hlstats_Servers WHERE game='$game' AND lat IS NOT NULL AND lng IS NOT NULL");
    
    $servers = array();
    while ($row = $db->fetch_array()) {
    
        $servers[] = array('lat'=>$row['lat'],
                           'lng' => $row['lng'],
                           'city' => $row['city'],
                           'country' => $row['country'],
                           'serverId' => $row['serverId'],
                           'name' => $row['name'],
                           'addr' => $row['addr'],
                           'name' => $row['name'],
                           'kills' => $row['kills']);
    
    }
    echo "<script>createServer(" . json_encode($servers) . ");</script>";
    // Players
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
        $stamp = time() - $row['connected'];
        $hours = sprintf("%02d", floor($stamp / 3600));
        $min = sprintf("%02d", floor(($stamp % 3600) / 60));
        $sec = sprintf("%02d", floor($stamp % 60));
        $time_str = $hours . ":" . $min . ":" . $sec;
    
        $players[] = array('cli_lat'=>$row['cli_lat'],
                           'cli_lng' => $row['cli_lng'],
                           'cli_city' => $row['cli_city'],
                           'cli_country' => $row['cli_country'],
                           'playerId' => $row['player_id'],
                           'name' => $row['name'],
                           'kills' => $row['kills'],
                           'deaths' => $row['deaths'],
                           'connected' => $time_str);
    }
}

if ( $type ==  "clan" ) {
    $db->query("SELECT
                   playerId,
                   lastName,
                   country,
                   skill,
                   kills,
                   deaths,
                   lat,
                   lng,
                   city,
                   country,
                   last_event
               FROM
                  hlstats_Players
               WHERE
                   clan=$clan
                   AND hideranking = 0
                   AND lat IS NOT NULL 
              ");
    $players = array();
    while ($row = $db->fetch_array())
    {
        $time_str = "Last seen: " .date("Y-m-d H:i:s", $row['last_event']);
    
        $players[] = array('cli_lat'=>$row['lat'],
                           'cli_lng' => $row['lng'],
                           'cli_city' => $row['city'],
                           'cli_country' => $row['country'],
                           'playerId' => $row['playerId'],
                           'name' => $row['lastName'],
                           'kills' => $row['kills'],
                           'deaths' => $row['deaths'],
                           'connected' => $time_str);
    }
}
echo "<script>createPlayer(" . json_encode($players) . ");</script>";
?>