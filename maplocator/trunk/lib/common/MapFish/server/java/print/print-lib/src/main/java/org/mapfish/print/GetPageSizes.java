package org.mapfish.print;

import com.lowagie.text.PageSize;
import com.lowagie.text.Rectangle;

import java.lang.reflect.Field;
import java.lang.reflect.Modifier;

public class GetPageSizes {
    public static void main(String[] args) throws IllegalAccessException {
        Field[] fields = PageSize.class.getDeclaredFields();
        for (int i = 0; i < fields.length; i++) {
            Field field = fields[i];
            if (Modifier.isStatic(field.getModifiers())) {
                try {
                    Rectangle val = (Rectangle) field.get(null);
                    System.out.println(field.getName() + ": " + Math.round(val.getWidth()) + "x" + Math.round(val.getHeight()));
                } catch (Throwable ex) {
                    System.out.println("Error with: " + field.getModifiers());
                }
            }
        }
    }
}
