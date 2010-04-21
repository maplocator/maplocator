/*
 * Copyright (C) 2008  Camptocamp
 *
 * This file is part of MapFish Server
 *
 * MapFish Server is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MapFish Server is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with MapFish Server.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.mapfish.print.map.readers;

import org.mapfish.print.PrintTestCase;
import org.xml.sax.SAXException;

import javax.xml.parsers.ParserConfigurationException;
import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.Arrays;

public class WMSServerInfoTest extends PrintTestCase {
    public WMSServerInfoTest(String name) {
        super(name);
    }

    public void testParseTileCache() throws IOException, SAXException, ParserConfigurationException {
        String response = "<?xml version='1.0' encoding=\"ISO-8859-1\" standalone=\"no\" ?>\n" +
                "        <!DOCTYPE WMT_MS_Capabilities SYSTEM \n" +
                "            \"http://schemas.opengeospatial.net/wms/1.1.1/WMS_MS_Capabilities.dtd\" [\n" +
                "              <!ELEMENT VendorSpecificCapabilities (TileSet*) >\n" +
                "              <!ELEMENT TileSet (SRS, BoundingBox?, Resolutions,\n" +
                "                                 Width, Height, Format, Layers*, Styles*) >\n" +
                "              <!ELEMENT Resolutions (#PCDATA) >\n" +
                "              <!ELEMENT Width (#PCDATA) >\n" +
                "              <!ELEMENT Height (#PCDATA) >\n" +
                "              <!ELEMENT Layers (#PCDATA) >\n" +
                "              <!ELEMENT Styles (#PCDATA) >\n" +
                "        ]> \n" +
                "        <WMT_MS_Capabilities version=\"1.1.1\">\n" +
                "\n" +
                "          <Service>\n" +
                "            <Name>OGC:WMS</Name>\n" +
                "            <Title></Title>\n" +
                "            <OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com?\"/>\n" +
                "          </Service>\n" +
                "        \n" +
                "          <Capability>\n" +
                "            <Request>\n" +
                "              <GetCapabilities>\n" +
                "\n" +
                "                <Format>application/vnd.ogc.wms_xml</Format>\n" +
                "                <DCPType>\n" +
                "                  <HTTP>\n" +
                "                    <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com?\"/></Get>\n" +
                "                  </HTTP>\n" +
                "                </DCPType>\n" +
                "              </GetCapabilities>\n" +
                "              <GetMap>\n" +
                "\n" +
                "                <Format>image/png</Format>\n" +
                "\n" +
                "                <DCPType>\n" +
                "                  <HTTP>\n" +
                "                    <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com?\"/></Get>\n" +
                "                  </HTTP>\n" +
                "                </DCPType>\n" +
                "              </GetMap>\n" +
                "            </Request>\n" +
                "\n" +
                "            <Exception>\n" +
                "              <Format>text/plain</Format>\n" +
                "            </Exception>\n" +
                "            <VendorSpecificCapabilities>\n" +
                "              <TileSet>\n" +
                "                <SRS>EPSG:21781</SRS>\n" +
                "                <BoundingBox SRS=\"EPSG:21781\" minx=\"155000.000000\" miny=\"-253050.000000\"\n" +
                "                                      maxx=\"1365000.000000\" maxy=\"583050.000000\" />\n" +
                "                <Resolutions>800.00000000000000000000 400.00000000000000000000 200.00000000000000000000 100.00000000000000000000 50.00000000000000000000 20.00000000000000000000 10.00000000000000000000 5.00000000000000000000 2.50000000000000000000</Resolutions>\n" +
                "\n" +
                "                <Width>256</Width>\n" +
                "                <Height>256</Height>\n" +
                "                <Format>image/png</Format>\n" +
                "                <Layers>cn</Layers>\n" +
                "                <Styles></Styles>\n" +
                "              </TileSet>\n" +
                "            </VendorSpecificCapabilities>\n" +
                "            <UserDefinedSymbolization SupportSLD=\"0\" UserLayer=\"0\"\n" +
                "                                      UserStyle=\"0\" RemoteWFS=\"0\"/>\n" +
                "            <Layer>\n" +
                "              <Title>TileCache Layers</Title>\n" +
                "            <Layer queryable=\"0\" opaque=\"0\" cascaded=\"1\">\n" +
                "\n" +
                "              <Name>cn</Name>\n" +
                "              <Title>cn</Title>\n" +
                "              <SRS>EPSG:21781</SRS>\n" +
                "              <BoundingBox SRS=\"EPSG:21781\" minx=\"155000.000000\" miny=\"-253050.000000\"\n" +
                "                                    maxx=\"1365000.000000\" maxy=\"583050.000000\" />\n" +
                "            </Layer>\n" +
                "            </Layer>\n" +
                "          </Capability>\n" +
                "        </WMT_MS_Capabilities>";

        InputStream stream = new ByteArrayInputStream(response.getBytes("ISO-8859-1"));
        WMSServerInfo info = WMSServerInfo.parseCapabilities(stream);
        assertEquals(true, info.isTileCache());
        TileCacheLayerInfo layerInfo = info.getTileCacheLayer("cn");
        assertNotNull(layerInfo);
        assertEquals(256, layerInfo.getWidth());
        assertEquals(256, layerInfo.getHeight());
        final float[] resolutions = layerInfo.getResolutions();
        final float[] expectedResolutions = {
                800.0F,
                400.0F,
                200.0F,
                100.0F,
                50.0F,
                20.0F,
                10.0F,
                5.0F,
                2.5F};
        assertTrue(Arrays.equals(expectedResolutions, resolutions));

        final TileCacheLayerInfo.ResolutionInfo higherRes = new TileCacheLayerInfo.ResolutionInfo(8, 2.5F);
        final TileCacheLayerInfo.ResolutionInfo midRes = new TileCacheLayerInfo.ResolutionInfo(7, 5.0F);
        final TileCacheLayerInfo.ResolutionInfo lowerRes = new TileCacheLayerInfo.ResolutionInfo(0, 800.0F);

        assertEquals(higherRes, layerInfo.getNearestResolution(0.1F));
        assertEquals(higherRes, layerInfo.getNearestResolution(2.5F));
        assertEquals(higherRes, layerInfo.getNearestResolution(2.6F));
        assertEquals(midRes, layerInfo.getNearestResolution(4.99999F));
        assertEquals(midRes, layerInfo.getNearestResolution(5.0F));
        assertEquals(lowerRes, layerInfo.getNearestResolution(1000.0F));

        assertEquals(155000.0F, layerInfo.getMinX());
        assertEquals(-253050.0F, layerInfo.getMinY());
        assertEquals("png", layerInfo.getExtension());
    }

    /**
     * Tilecache with resolutions not in the correct order.
     */
    public void testParseWeirdTileCache() throws IOException, SAXException, ParserConfigurationException {
        String response = "<?xml version='1.0' encoding=\"ISO-8859-1\" standalone=\"no\" ?>\n" +
                "        <!DOCTYPE WMT_MS_Capabilities SYSTEM \n" +
                "            \"http://schemas.opengeospatial.net/wms/1.1.1/WMS_MS_Capabilities.dtd\" [\n" +
                "              <!ELEMENT VendorSpecificCapabilities (TileSet*) >\n" +
                "              <!ELEMENT TileSet (SRS, BoundingBox?, Resolutions,\n" +
                "                                 Width, Height, Format, Layers*, Styles*) >\n" +
                "              <!ELEMENT Resolutions (#PCDATA) >\n" +
                "              <!ELEMENT Width (#PCDATA) >\n" +
                "              <!ELEMENT Height (#PCDATA) >\n" +
                "              <!ELEMENT Layers (#PCDATA) >\n" +
                "              <!ELEMENT Styles (#PCDATA) >\n" +
                "        ]> \n" +
                "        <WMT_MS_Capabilities version=\"1.1.1\">\n" +
                "\n" +
                "          <Service>\n" +
                "            <Name>OGC:WMS</Name>\n" +
                "            <Title></Title>\n" +
                "            <OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com?\"/>\n" +
                "          </Service>\n" +
                "        \n" +
                "          <Capability>\n" +
                "            <Request>\n" +
                "              <GetCapabilities>\n" +
                "\n" +
                "                <Format>application/vnd.ogc.wms_xml</Format>\n" +
                "                <DCPType>\n" +
                "                  <HTTP>\n" +
                "                    <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com?\"/></Get>\n" +
                "                  </HTTP>\n" +
                "                </DCPType>\n" +
                "              </GetCapabilities>\n" +
                "              <GetMap>\n" +
                "\n" +
                "                <Format>image/png</Format>\n" +
                "\n" +
                "                <DCPType>\n" +
                "                  <HTTP>\n" +
                "                    <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com?\"/></Get>\n" +
                "                  </HTTP>\n" +
                "                </DCPType>\n" +
                "              </GetMap>\n" +
                "            </Request>\n" +
                "\n" +
                "            <Exception>\n" +
                "              <Format>text/plain</Format>\n" +
                "            </Exception>\n" +
                "            <VendorSpecificCapabilities>\n" +
                "              <TileSet>\n" +
                "                <SRS>EPSG:21781</SRS>\n" +
                "                <BoundingBox SRS=\"EPSG:21781\" minx=\"155000.000000\" miny=\"-253050.000000\"\n" +
                "                                      maxx=\"1365000.000000\" maxy=\"583050.000000\" />\n" +
                "                <Resolutions>400.00000000000000000000 800.00000000000000000000 200.00000000000000000000 100.00000000000000000000 50.00000000000000000000 20.00000000000000000000 10.00000000000000000000 5.00000000000000000000 2.50000000000000000000</Resolutions>\n" +
                "\n" +
                "                <Width>256</Width>\n" +
                "                <Height>256</Height>\n" +
                "                <Format>image/png</Format>\n" +
                "                <Layers>cn</Layers>\n" +
                "                <Styles></Styles>\n" +
                "              </TileSet>\n" +
                "            </VendorSpecificCapabilities>\n" +
                "            <UserDefinedSymbolization SupportSLD=\"0\" UserLayer=\"0\"\n" +
                "                                      UserStyle=\"0\" RemoteWFS=\"0\"/>\n" +
                "            <Layer>\n" +
                "              <Title>TileCache Layers</Title>\n" +
                "            <Layer queryable=\"0\" opaque=\"0\" cascaded=\"1\">\n" +
                "\n" +
                "              <Name>cn</Name>\n" +
                "              <Title>cn</Title>\n" +
                "              <SRS>EPSG:21781</SRS>\n" +
                "              <BoundingBox SRS=\"EPSG:21781\" minx=\"155000.000000\" miny=\"-253050.000000\"\n" +
                "                                    maxx=\"1365000.000000\" maxy=\"583050.000000\" />\n" +
                "            </Layer>\n" +
                "            </Layer>\n" +
                "          </Capability>\n" +
                "        </WMT_MS_Capabilities>";

        InputStream stream = new ByteArrayInputStream(response.getBytes("ISO-8859-1"));
        WMSServerInfo info = WMSServerInfo.parseCapabilities(stream);
        assertEquals(true, info.isTileCache());
        TileCacheLayerInfo layerInfo = info.getTileCacheLayer("cn");
        assertNotNull(layerInfo);
        assertEquals(256, layerInfo.getWidth());
        assertEquals(256, layerInfo.getHeight());
        final float[] resolutions = layerInfo.getResolutions();
        final float[] expectedResolutions = {
                800.0F,
                400.0F,
                200.0F,
                100.0F,
                50.0F,
                20.0F,
                10.0F,
                5.0F,
                2.5F};
        assertTrue(Arrays.equals(expectedResolutions, resolutions));

        final TileCacheLayerInfo.ResolutionInfo higherRes = new TileCacheLayerInfo.ResolutionInfo(8, 2.5F);
        final TileCacheLayerInfo.ResolutionInfo midRes = new TileCacheLayerInfo.ResolutionInfo(7, 5.0F);
        final TileCacheLayerInfo.ResolutionInfo lowerRes = new TileCacheLayerInfo.ResolutionInfo(0, 800.0F);

        assertEquals(higherRes, layerInfo.getNearestResolution(0.1F));
        assertEquals(higherRes, layerInfo.getNearestResolution(2.5F));
        assertEquals(higherRes, layerInfo.getNearestResolution(2.6F));
        assertEquals(midRes, layerInfo.getNearestResolution(4.99999F));
        assertEquals(midRes, layerInfo.getNearestResolution(5.0F));
        assertEquals(lowerRes, layerInfo.getNearestResolution(1000.0F));

        assertEquals(155000.0F, layerInfo.getMinX());
        assertEquals(-253050.0F, layerInfo.getMinY());
        assertEquals("png", layerInfo.getExtension());
    }

    public void testParseMapServer() throws IOException, SAXException, ParserConfigurationException {
        String response = "<?xml version='1.0' encoding=\"UTF-8\" standalone=\"no\" ?>\n" +
                "<!DOCTYPE WMT_MS_Capabilities SYSTEM \"http://schemas.opengis.net/wms/1.1.1/WMS_MS_Capabilities.dtd\"\n" +
                " [\n" +
                " <!ELEMENT VendorSpecificCapabilities EMPTY>\n" +
                " ]>  <!-- end of DOCTYPE declaration -->\n" +
                "\n" +
                "<WMT_MS_Capabilities version=\"1.1.1\">\n" +
                "\n" +
                "<!-- MapServer version 5.0.3 OUTPUT=GIF OUTPUT=PNG OUTPUT=JPEG OUTPUT=WBMP OUTPUT=SVG SUPPORTS=PROJ SUPPORTS=AGG SUPPORTS=FREETYPE SUPPORTS=WMS_SERVER SUPPORTS=WMS_CLIENT SUPPORTS=WFS_SERVER SUPPORTS=WFS_CLIENT SUPPORTS=WCS_SERVER SUPPORTS=FASTCGI SUPPORTS=THREADS SUPPORTS=GEOS INPUT=EPPL7 INPUT=POSTGIS INPUT=OGR INPUT=GDAL INPUT=SHAPEFILE -->\n" +
                "\n" +
                "<Service>\n" +
                "  <Name>OGC:WMS</Name>\n" +
                "  <Title>SwissTopo raster WMS Server</Title>\n" +
                "  <Abstract>WMS Server serving swisstopo raster maps</Abstract>\n" +
                "  <OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/>\n" +
                "  <ContactInformation>\n" +
                "  </ContactInformation>\n" +
                "</Service>\n" +
                "\n" +
                "<Capability>\n" +
                "  <Request>\n" +
                "    <GetCapabilities>\n" +
                "      <Format>application/vnd.ogc.wms_xml</Format>\n" +
                "      <DCPType>\n" +
                "        <HTTP>\n" +
                "          <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Get>\n" +
                "          <Post><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Post>\n" +
                "        </HTTP>\n" +
                "      </DCPType>\n" +
                "    </GetCapabilities>\n" +
                "    <GetMap>\n" +
                "      <Format>image/tiff</Format>\n" +
                "      <Format>image/gif</Format>\n" +
                "      <Format>image/png; mode=24bit</Format>\n" +
                "      <Format>image/wbmp</Format>\n" +
                "      <Format>image/svg+xml</Format>\n" +
                "      <DCPType>\n" +
                "        <HTTP>\n" +
                "          <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Get>\n" +
                "          <Post><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Post>\n" +
                "        </HTTP>\n" +
                "      </DCPType>\n" +
                "    </GetMap>\n" +
                "    <GetFeatureInfo>\n" +
                "      <Format>text/plain</Format>\n" +
                "      <Format>application/vnd.ogc.gml</Format>\n" +
                "      <DCPType>\n" +
                "        <HTTP>\n" +
                "          <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Get>\n" +
                "          <Post><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Post>\n" +
                "        </HTTP>\n" +
                "      </DCPType>\n" +
                "    </GetFeatureInfo>\n" +
                "    <DescribeLayer>\n" +
                "      <Format>text/xml</Format>\n" +
                "      <DCPType>\n" +
                "        <HTTP>\n" +
                "          <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Get>\n" +
                "          <Post><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Post>\n" +
                "        </HTTP>\n" +
                "      </DCPType>\n" +
                "    </DescribeLayer>\n" +
                "    <GetLegendGraphic>\n" +
                "      <Format>image/gif</Format>\n" +
                "      <Format>image/png; mode=24bit</Format>\n" +
                "      <Format>image/wbmp</Format>\n" +
                "      <DCPType>\n" +
                "        <HTTP>\n" +
                "          <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Get>\n" +
                "          <Post><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Post>\n" +
                "        </HTTP>\n" +
                "      </DCPType>\n" +
                "    </GetLegendGraphic>\n" +
                "    <GetStyles>\n" +
                "      <Format>text/xml</Format>\n" +
                "      <DCPType>\n" +
                "        <HTTP>\n" +
                "          <Get><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Get>\n" +
                "          <Post><OnlineResource xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"http://www.example.com/cgi-bin/mapserver?\"/></Post>\n" +
                "        </HTTP>\n" +
                "      </DCPType>\n" +
                "    </GetStyles>\n" +
                "  </Request>\n" +
                "  <Exception>\n" +
                "    <Format>application/vnd.ogc.se_xml</Format>\n" +
                "    <Format>application/vnd.ogc.se_inimage</Format>\n" +
                "    <Format>application/vnd.ogc.se_blank</Format>\n" +
                "  </Exception>\n" +
                "  <VendorSpecificCapabilities />\n" +
                "  <UserDefinedSymbolization SupportSLD=\"1\" UserLayer=\"0\" UserStyle=\"1\" RemoteWFS=\"0\"/>\n" +
                "  <Layer>\n" +
                "    <Name>SwissTopo</Name>\n" +
                "    <Title>SwissTopo raster WMS Server</Title>\n" +
                "    <SRS>epsg:21781</SRS>\n" +
                "    <SRS>epsg:4326</SRS>\n" +
                "    <LatLonBoundingBox minx=\"1.20539\" miny=\"42.4702\" maxx=\"18.1119\" maxy=\"50.3953\" />\n" +
                "    <BoundingBox SRS=\"EPSG:21781\"\n" +
                "                minx=\"155000\" miny=\"-253050\" maxx=\"1.365e+06\" maxy=\"583050\" />\n" +
                "    <Layer>\n" +
                "      <Name>cn</Name>\n" +
                "      <Title>SwissTopo</Title>\n" +
                "      <Abstract>cn</Abstract>\n" +
                "      <Layer queryable=\"0\" opaque=\"0\" cascaded=\"0\">\n" +
                "        <Name>cn25k</Name>\n" +
                "        <Title>cn25k</Title>\n" +
                "        <SRS>epsg:21781</SRS>\n" +
                "        <SRS>epsg:4326</SRS>\n" +
                "        <ScaleHint min=\"0.0707106399349092\" max=\"5.23258735518328\" />\n" +
                "      </Layer>\n" +
                "    </Layer>\n" +
                "  </Layer>\n" +
                "\n" +
                "</Capability>\n" +
                "</WMT_MS_Capabilities>";

        InputStream stream = new ByteArrayInputStream(response.getBytes("ISO-8859-1"));
        WMSServerInfo info = WMSServerInfo.parseCapabilities(stream);
        assertEquals(false, info.isTileCache());
    }
}
