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

import org.mapfish.print.RenderingContext;
import org.mapfish.print.Transformer;
import org.mapfish.print.map.renderers.MapRenderer;
import org.mapfish.print.utils.PJsonArray;
import org.mapfish.print.utils.PJsonObject;
import org.pvalsecc.misc.StringUtils;
import org.pvalsecc.misc.URIUtils;

import java.io.UnsupportedEncodingException;
import java.net.URI;
import java.net.URISyntaxException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Support for the WMS protocol with possibilities to go through a WMS-C service
 * (TileCache).
 */
public class WMSMapReader extends TileableMapReader {
    private final List<String> layers = new ArrayList<String>();

    private final String format;

    private final List<String> styles = new ArrayList<String>();

    private WMSMapReader(String layer, String style, RenderingContext context, PJsonObject params) {
        super(context, params);
        tileCacheLayerInfo = WMSServerInfo.getInfo(baseUrl, context).getTileCacheLayer(layer);
        layers.add(layer);
        styles.add(style);
        format = params.getString("format");
    }

    protected MapRenderer.Format getFormat() {
        if (format.equals("image/svg+xml")) {
            return MapRenderer.Format.SVG;
        } else if (format.equals("application/x-pdf")) {
            return MapRenderer.Format.PDF;
        } else {
            return MapRenderer.Format.BITMAP;
        }
    }

    protected void addCommonQueryParams(Map<String, List<String>> result, Transformer transformer, String srs, boolean first) {
        URIUtils.addParamOverride(result, "FORMAT", format);
        URIUtils.addParamOverride(result, "LAYERS", StringUtils.join(layers, ","));
        URIUtils.addParamOverride(result, "SRS", srs);
        URIUtils.addParamOverride(result, "SERVICE", "WMS");
        URIUtils.addParamOverride(result, "REQUEST", "GetMap");
        //URIUtils.addParamOverride(result, "EXCEPTIONS", "application/vnd.ogc.se_inimage");
        URIUtils.addParamOverride(result, "VERSION", "1.1.1");
        if (!first) {
            URIUtils.addParamOverride(result, "TRANSPARENT", "true");
        }
        URIUtils.addParamOverride(result, "STYLES", StringUtils.join(styles, ","));
    }

    protected static void create(List<MapReader> target, RenderingContext context, PJsonObject params) {
        PJsonArray layers = params.getJSONArray("layers");
        PJsonArray styles = params.optJSONArray("styles");
        for (int i = 0; i < layers.size(); i++) {
            String layer = layers.getString(i);
            String style = "";
            if (styles != null && i < styles.size()) {
                style = styles.getString(i);
            }
            target.add(new WMSMapReader(layer, style, context, params));
        }
    }

    public boolean testMerge(MapReader other) {
        if (canMerge(other)) {
            WMSMapReader wms = (WMSMapReader) other;
            layers.addAll(wms.layers);
            styles.addAll(wms.styles);
            return true;
        } else {
            return false;
        }
    }

    public boolean canMerge(MapReader other) {
        if (!super.canMerge(other)) {
            return false;
        }

        if (tileCacheLayerInfo != null) {
            //no layer merge when tilecache is here...
            return false;  //TODO: the new versions of tilecache do support layer merge, but there is curently no mean to know what version of tilecache we are dealing with
        }

        if (other instanceof WMSMapReader) {
            WMSMapReader wms = (WMSMapReader) other;
            return format.equals(wms.format);
        } else {
            return false;
        }
    }

    protected URI getTileUri(URI commonUri, Transformer transformer, float minGeoX, float minGeoY, float maxGeoX, float maxGeoY, long w, long h) throws URISyntaxException, UnsupportedEncodingException {

        Map<String, List<String>> tileParams = new HashMap<String, List<String>>();
        if (format.equals("image/svg+xml")) {
            URIUtils.addParamOverride(tileParams, "WIDTH", Long.toString(transformer.getRotatedSvgW()));
            URIUtils.addParamOverride(tileParams, "HEIGHT", Long.toString(transformer.getRotatedSvgH()));
        } else {
            URIUtils.addParamOverride(tileParams, "WIDTH", Long.toString(w));
            URIUtils.addParamOverride(tileParams, "HEIGHT", Long.toString(h));
        }
        URIUtils.addParamOverride(tileParams, "BBOX", String.format("%s,%s,%s,%s", minGeoX, minGeoY, maxGeoX, maxGeoY));
        return URIUtils.addParams(commonUri, tileParams, OVERRIDE_ALL);
    }

    public String toString() {
        return StringUtils.join(layers, ", ");
    }
}
