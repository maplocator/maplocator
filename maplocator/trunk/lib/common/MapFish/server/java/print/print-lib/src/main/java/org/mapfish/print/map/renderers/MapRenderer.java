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

package org.mapfish.print.map.renderers;

import com.lowagie.text.pdf.PdfContentByte;
import org.mapfish.print.RenderingContext;
import org.mapfish.print.Transformer;

import java.io.IOException;
import java.net.URI;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public abstract class MapRenderer {
    private static final Map<Format, MapRenderer> renderers = new HashMap<Format, MapRenderer>();

    static {
        renderers.put(Format.BITMAP, new BitmapMapRenderer());
        renderers.put(Format.PDF, new PDFMapRenderer());
        renderers.put(Format.SVG, new SVGMapRenderer());
    }

    public static MapRenderer get(Format format) {
        return renderers.get(format);
    }

    public abstract void render(Transformer transformer, List<URI> urls, PdfContentByte dc, RenderingContext context, float opacity, int nbTilesHorizontal, float offsetX, float offsetY, long bitmapTileW, long bitmapTileH) throws IOException;

    public enum Format {
        BITMAP,
        PDF,
        SVG
    }
}
