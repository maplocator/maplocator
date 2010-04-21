/**
 * @requires OpenLayers/Lang/en.js
 */
OpenLayers.Util.extend(OpenLayers.Lang.de, {
    'mf.layertree.opacity': 'Opazität',
    'mf.layertree.remove': 'Ausblenden',
    'mf.layertree.zoomToExtent': 'Zoom object zoomen', //TODO: find a better translation
    'mf.print.mapTitle': 'Titel',
    'mf.print.comment': 'Bemerkung',
    'mf.print.loadingConfig': 'Laden der Konfiguration...',
    'mf.print.serverDown': ' Der Druck-Systemdienst funktioniert nicht',
    'mf.print.unableToPrint': "Unable to print",
    'mf.print.generatingPDF': "Generierung des PDFs...",
    'mf.print.dpi': 'DPI',
    'mf.print.scale': 'Massstab',
    'mf.print.rotation': 'Rotation',
    'mf.print.print': 'Drücken',
    'mf.print.resetPos': 'Reset Pos.',
    'mf.print.layout': 'Layout',
    'mf.print.addPage': 'Seite hinzufügen',
    'mf.print.remove': 'Seite entfernen',
    'mf.print.clearAll': 'Alles löschen',
    'mf.print.pdfReady': 'Das PDF-Dokument kann heruntergeladen werden.',
    'mf.print.noPage': 'Keine Seite ausgewählt, bitte auf "' + this['mf.print.addPage'] + '"-' +
                       'Button klicken um eine Seite zu hinzufügen.',
    'mf.print.print-tooltip': 'Generate a PDF with at least the extent shown on the map', // TODO
    'mf.error': 'Fehler',
    'mf.warning': 'Warnung',
    'mf.information': 'Information',
    'mf.recenter.x': 'X',
    'mf.recenter.y': 'Y',
    'mf.recenter.submit': 'Recenter', // TODO
    'mf.recenter.missingCoords': 'Some coordinates are missing.', // TODO
    'mf.recenter.outOfRangeCoords': 'Submitted coordinates (${myX}, ${myY}) are not in the map area<br />' +
                                    'and must be within following ranges:<br/>' +
                                    '${coordX} between ${minCoordX} and ${maxCoordX},<br />' +
                                    '${coordY} between ${minCoordY} and ${maxCoordY}', // TODO
    'mf.recenter.ws.error': 'Ein Fehler ist bei Zugang zum Webdienst vorgekommen:',
    'mf.recenter.ws.service': 'Ausgewählter Webdienst'
});
