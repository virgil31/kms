Ext.define('CL.view.utils.imageviewer.ImageViewer', {
    extend: 'Ext.container.Container',

    alias: 'widget.imageviewer',

    layout: {
        type: 'vbox',
        align: 'stretch'
    },

    config: {
        isMoving: false,
        imageWidth: null,
        imageHeight: null,
        originalImageWidth: null,
        originalImageHeight: null,
        clickX: null,
        clickY: null,
        lastMarginX: null,
        lastMarginY: null,
        rotation: 0
    },

    initComponent: function () {
        var me = this;

        me.tooltips = me.tooltips || {};

        me.tooltips = Ext.applyIf(me.tooltips, {
            stretchHorizontally: 'Stretch horizontally',
            stretchVertically: 'Stretch vertically',
            stretchOptimally: 'Stretch optimally',
            zoomIn: 'Zoom in',
            zoomOut: 'Zoom out',
            rotateClockwise: 'Rotate clockwise',
            rotateAntiClockwise: 'Rotate anticlockwise'
        });

        me.items = [{
            xtype: 'toolbar',
            defaults: {
                tooltipType: 'title'
            },
            items: [/*{
                xtype: 'button',
                tooltip: me.tooltips.stretchHorizontally,
                icon: 'app/view/utils/imageviewer/resources/images/imageviewer/stretch_horizontally.png',
                listeners: { click: me.stretchHorizontally, scope: me }
            }, {
                xtype: 'button',
                tooltip: me.tooltips.stretchVertically,
                icon: 'app/view/utils/imageviewer/resources/images/imageviewer/stretch_vertically.png',
                listeners: { click: me.stretchVertically, scope: me }
            },*/ {
                xtype: 'button',
                tooltip: me.tooltips.stretchOptimally,
                icon: 'app/view/utils/imageviewer/resources/images/imageviewer/stretch_optimally.png',
                listeners: { click: me.stretchOptimally, scope: me }
            }, {
                xtype: 'button',
                tooltip: me.tooltips.zoomIn,
                name: 'btn_zoom_in',
                icon: 'app/view/utils/imageviewer/resources/images/imageviewer/zoom_in.png',
                listeners: { click: me.zoomIn, scope: me }
            }, {
                xtype: 'button',
                tooltip: me.tooltips.zoomOut,
                name: 'btn_zoom_out',
                icon: 'app/view/utils/imageviewer/resources/images/imageviewer/zoom_out.png',
                listeners: { click: me.zoomOut, scope: me }
            }, {
                xtype: 'button',
                tooltip: me.tooltips.rotateClockwise,
                icon: 'app/view/utils/imageviewer/resources/images/imageviewer/arrow_rotate_clockwise.png',
                listeners: { click: me.rotateClockwise, scope: me }
            }, {
                xtype: 'button',
                tooltip: me.tooltips.rotateAntiClockwise,
                icon: 'app/view/utils/imageviewer/resources/images/imageviewer/arrow_rotate_anticlockwise.png',
                listeners: { click: me.rotateAntiClockwise, scope: me }
            }]
        }, {
            xtype: 'container',
            itemId: 'imagecontainer',
            flex: 1,
            style: {
                overflow: 'hidden',
                backgroundColor: '#f2f1f0',
                backgroundImage: "url('app/view/utils/imageviewer/resources/images/imageviewer/loading.gif')",
                backgroundPosition: 'center',
                backgroundRepeat: 'no-repeat',
                padding: '10px',
                cursor: 'move'
            },
            items: {
                xtype: 'image',
                mode: 'element',
                src: me.src,
                hidden: true,
                /*style: {
                    boxShadow: '0 0 5px 5px #888'
                },*/
                listeners: {
                    render: function (image) {

                        image.el.on('mousewheel',me.mouseWheelHandler);
                        image.el.dom.onload = function () {

                            image.el.setStyle({
                                boxShadow: '0 0 5px 5px #888'
                            });

                            // vv per rimouvere la loading.gif
                            Ext.ComponentQuery.query('container[itemId=imagecontainer]')[0].el.setStyle({
                                backgroundImage: 'none'
                            });
                            //Ext.ComponentQuery.query('container[itemId=imagecontainer]')[0].doLayout();
                            // ^^

                            me.setRotation(0);
                            me.rotateImage();
                            me.setOriginalImageWidth(image.el.dom.width);
                            me.setOriginalImageHeight(image.el.dom.height);
                            me.setImageWidth(image.el.dom.width);
                            me.setImageHeight(image.el.dom.height);
                            me.stretchOptimally();

                            image.setVisible(true);
                        };
                    }
                }

            }
        }];

        me.callParent();
    },

    mouseWheelHandler: function(e){
        e.preventDefault(); //per evitare che lanci gli eventi di scroll e zoommi solamente

        var delta = e.getWheelDelta();
        if(delta > 0)
            Ext.ComponentQuery.query('button[name=btn_zoom_in]')[0].el.dom.click();
        else
            Ext.ComponentQuery.query('button[name=btn_zoom_out]')[0].el.dom.click();
    },

    initEvents: function () {
        var me = this;

        me.mon(me.getImageContainer().getEl(), {
            mouseup: me.mouseup,
            mousedown: me.mousedown,
            mousemove: me.mousemove,
            scope: me
        });

        me.callParent();
    },

    stretchHorizontally: function () {
        var me = this,
            imageContainerWidth = me.getImageContainer().getWidth();

        me.setImageSize({
            width: imageContainerWidth - 20,
            height: me.getOriginalImageHeight() * (imageContainerWidth - 20) / me.getOriginalImageWidth()
        });

        me.centerImage();
    },

    stretchVertically: function () {
        var me = this,
            imageContainerHeight = me.getImageContainer().getHeight();

        me.setImageSize({
            width: me.getOriginalImageWidth() * (imageContainerHeight - 20) / me.getOriginalImageHeight(),
            height: imageContainerHeight - 20
        });

        me.centerImage();
    },

    stretchOptimally: function () {
        var me = this,
            imageContainer = me.getImageContainer(),
            adjustedImageSize = me.getAdjustedImageSize();

        if (adjustedImageSize.width * imageContainer.getHeight() / adjustedImageSize.height > imageContainer.getWidth()) {
            me.stretchHorizontally();
        } else {
            me.stretchVertically();
        }
    },

    centerImage: function () {
        var me = this,
            imageContainer = me.getImageContainer(),
            adjustedImageSize = me.getAdjustedImageSize();

        me.setMargins({
            top: (imageContainer.getHeight() - adjustedImageSize.height - 20) / 2,
            left: (imageContainer.getWidth() - adjustedImageSize.width - 20) / 2
        });
    },

    mousedown: function (event) {
        var me = this,
            margins = me.getMargins();

        event.stopEvent();

        me.setClickX(event.getX());
        me.setClickY(event.getY());
        me.setLastMarginY(margins.top);
        me.setLastMarginX(margins.left);

        me.setIsMoving(true);
    },

    mousemove: function (event) {
        var me = this;

        if (me.getIsMoving()) {
            me.setMargins({
                top: me.getLastMarginY() - me.getClickY() + event.getY(),
                left: me.getLastMarginX() - me.getClickX() + event.getX()
            });
        }
    },

    mouseup: function () {
        var me = this;

        if (me.getIsMoving()) {
            me.setClickX(null);
            me.setClickY(null);
            me.setLastMarginX(null);
            me.setLastMarginY(null);
            me.setIsMoving(false);
        }
    },

    zoomOut: function (btn, event, opts) {
        var me = this,
            margins = me.getMargins(),
            adjustedImageSize = me.getAdjustedImageSize();

        me.setMargins({
            top: margins.top + adjustedImageSize.height * 0.05,
            left: margins.left + adjustedImageSize.width * 0.05
        });

        me.setImageSize({
            width: adjustedImageSize.width * 0.9,
            height: me.getOriginalImageHeight() * adjustedImageSize.width * 0.9 / me.getOriginalImageWidth()
        });

        event.stopEvent();
    },

    zoomIn: function (btn, event, opts) {
        var me = this,
            margins = me.getMargins(),
            adjustedImageSize = me.getAdjustedImageSize();

        me.setMargins({
            top: margins.top - adjustedImageSize.height * 0.05,
            left: margins.left - adjustedImageSize.width * 0.05
        });

        me.setImageSize({
            width: adjustedImageSize.width * 1.1,
            height: me.getOriginalImageHeight() * adjustedImageSize.width * 1.1 / me.getOriginalImageWidth()
        });

        event.stopEvent();
    },

    rotateClockwise: function () {
        var me = this,
            rotation = me.getRotation();

        rotation += 90;

        if (rotation > 360) {
            rotation -= 360;
        }

        me.setRotation(rotation);
        me.rotateImage();
    },

    rotateAntiClockwise: function () {
        var me = this,
            rotation = me.getRotation();

        rotation -= 90;

        if (rotation < 0) {
            rotation += 360;
        }

        me.setRotation(rotation);
        me.rotateImage();
    },

    rotateImage: function () {
        var me = this,
            tmpOriginalWidth,
            transformStyle = 'rotate(' + me.getRotation() + 'deg)';

        tmpOriginalWidth = me.getOriginalImageWidth();
        me.setOriginalImageWidth(me.getOriginalImageHeight());
        me.setOriginalImageHeight(tmpOriginalWidth);

        me.getImage().getEl().applyStyles({
            'transform': transformStyle,
            '-o-transform': transformStyle,
            '-ms-transform': transformStyle,
            '-moz-transform': transformStyle,
            '-webkit-transform': transformStyle
        });

        me.setMargins(me.getMargins());
    },

    setMargins: function (margins) {
        var me = this,
            rotation = me.getRotation(),
            adjustedImageSize = me.getAdjustedImageSize(),
            imageContainer = me.getImageContainer(),
            imageContainerWidth = imageContainer.getWidth(),
            imageContainerHeight = imageContainer.getHeight();

        if (adjustedImageSize.width > imageContainerWidth - 20) {
            if (margins.left > 0) {
                margins.left = 0;
            } else if (margins.left < imageContainerWidth - adjustedImageSize.width - 20) {
                margins.left = imageContainerWidth - adjustedImageSize.width - 20;
            }
        } else {
            if (margins.left < 0) {
                margins.left = 0;
            } else if (margins.left > imageContainerWidth - adjustedImageSize.width - 20) {
                margins.left = imageContainerWidth - adjustedImageSize.width - 20;
            }
        }

        if (adjustedImageSize.height > imageContainerHeight - 20) {
            if (margins.top > 0) {
                margins.top = 0;
            } else if (margins.top < imageContainerHeight - adjustedImageSize.height - 20) {
                margins.top = imageContainerHeight - adjustedImageSize.height - 20;
            }
        } else {
            if (margins.top < 0) {
                margins.top = 0;
            } else if (margins.top > imageContainerHeight - adjustedImageSize.height - 20) {
                margins.top = imageContainerHeight - adjustedImageSize.height - 20;
            }
        }

        if (rotation === 90 || rotation === 270) {
            var marginAdjustment = (me.getImageHeight() - me.getImageWidth()) / 2;
            margins.top = margins.top - marginAdjustment;
            margins.left = margins.left + marginAdjustment;
        }

        me.getImage().getEl().setStyle('margin-left', margins.left + 'px');
        me.getImage().getEl().setStyle('margin-top', margins.top + 'px');
    },

    getMargins: function () {
        var me = this,
            rotation = me.getRotation(),
            imageEl = me.getImage().getEl();

        var margins = {
            top: parseInt(imageEl.getStyle('margin-top'), 10),
            left: parseInt(imageEl.getStyle('margin-left'), 10)
        };

        if (rotation === 90 || rotation === 270) {
            var marginAdjustment = (me.getImageHeight() - me.getImageWidth()) / 2;
            margins.top = margins.top + marginAdjustment;
            margins.left = margins.left - marginAdjustment;
        }

        return margins;
    },

    getAdjustedImageSize: function () {
        var me = this,
            rotation = me.getRotation();

        if (rotation === 90 || rotation === 270) {
            return {
                width: me.getImageHeight(),
                height: me.getImageWidth()
            };
        } else {
            return {
                width: me.getImageWidth(),
                height: me.getImageHeight()
            };
        }
    },

    setImageSize: function (size) {
        var me = this,
            rotation = me.getRotation();

        if (rotation === 90 || rotation === 270) {
            me.setImageWidth(size.height);
            me.setImageHeight(size.width);
        } else {
            me.setImageWidth(size.width);
            me.setImageHeight(size.height);
        }
    },

    applyImageWidth: function (width) {
        var me = this;
        me.getImage().setWidth(width);
        return width;
    },

    applyImageHeight: function (height) {
        var me = this;
        me.getImage().setHeight(height);
        return height;
    },

    getImage: function () {
        return this.query('image')[0];
    },

    getImageContainer: function () {
        return this.query('#imagecontainer')[0];
    }
});
