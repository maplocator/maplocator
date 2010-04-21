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

package org.mapfish.print.config.layout;

import java.awt.*;

public class BorderConfig {
    protected Double borderWidthLeft = null;
    protected Double borderWidthRight = null;
    protected Double borderWidthTop = null;
    protected Double borderWidthBottom = null;
    protected Color borderColorLeft = null;
    protected Color borderColorRight = null;
    protected Color borderColorTop = null;
    protected Color borderColorBottom = null;

    public void setBorderColor(Color color) {
        setBorderColorLeft(color);
        setBorderColorRight(color);
        setBorderColorTop(color);
        setBorderColorBottom(color);
    }

    public void setBorderWidth(double border) {
        setBorderWidthLeft(border);
        setBorderWidthRight(border);
        setBorderWidthTop(border);
        setBorderWidthBottom(border);
    }

    public void setBorderWidthLeft(double borderWidthLeft) {
        this.borderWidthLeft = borderWidthLeft;
    }

    public void setBorderWidthRight(double borderWidthRight) {
        this.borderWidthRight = borderWidthRight;
    }

    public void setBorderWidthTop(double borderWidthTop) {
        this.borderWidthTop = borderWidthTop;
    }

    public void setBorderWidthBottom(double borderWidthBottom) {
        this.borderWidthBottom = borderWidthBottom;
    }

    public void setBorderColorLeft(Color borderColorLeft) {
        this.borderColorLeft = borderColorLeft;
    }

    public void setBorderColorRight(Color borderColorRight) {
        this.borderColorRight = borderColorRight;
    }

    public void setBorderColorTop(Color borderColorTop) {
        this.borderColorTop = borderColorTop;
    }

    public void setBorderColorBottom(Color borderColorBottom) {
        this.borderColorBottom = borderColorBottom;
    }
}
