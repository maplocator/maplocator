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
import com.lowagie.text.pdf.PdfGState;
import com.lowagie.text.pdf.PdfImportedPage;
import com.lowagie.text.pdf.PdfReader;
import org.apache.log4j.Logger;
import org.mapfish.print.InvalidValueException;
import org.mapfish.print.RenderingContext;
import org.mapfish.print.Transformer;

import java.io.IOException;
import java.net.URI;
import java.util.List;

public class PDFMapRenderer extends MapRenderer {
    public static final Logger LOGGER = Logger.getLogger(PDFMapRenderer.class);

    public void render(Transformer transformer, List<URI> uris, PdfContentByte dc, RenderingContext context, float opacity, int nbTilesHorizontal, float offsetX, float offsetY, long bitmapTileW, long bitmapTileH) throws IOException {
        if (uris.size() != 1) {
            //tiling not supported in PDF
            throw new InvalidValueException("format", "application/x-pdf");
        }
        final URI uri = uris.get(0);
        LOGGER.debug(uri);
        PdfReader reader = new PdfReader(uri.toURL());
        PdfImportedPage pdfMap = context.getWriter().getImportedPage(reader, 1);

        if (opacity < 1.0) {
            PdfGState gs = new PdfGState();
            gs.setFillOpacity(opacity);
            gs.setStrokeOpacity(opacity);
            //gs.setBlendMode(PdfGState.BM_SOFTLIGHT);
            pdfMap.setGState(gs);
        }

        dc.saveState();
        try {
            dc.transform(transformer.getPdfTransform());
            dc.addTemplate(pdfMap, 0, 0);
        } finally {
            dc.restoreState();
        }
    }
}
