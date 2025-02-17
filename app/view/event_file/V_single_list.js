Ext.define('CL.view.event_file.V_single_list', {
    extend: 'Ext.panel.Panel',
    xtype: 'event_file_single_list',
    itemId: 'event_file_single_list_id',
    alias: 'widget.event_file_single_list',

    layout: {
        type: 'vbox',
        align: 'center',
        pack: 'center'
    },

    bodyStyle: {
        background: "transparent"
    },

    items: [
        {
            xtype: 'panel',
            width: '100%',
            margin: '10 0 0 0',
            //bodyCls: 'mypanel',
            bodyStyle:{
                background: "url(images/profile_repeat.png)",
                borderRadius: "10px",
                border: "1px black solid !important"
            },
            layout: {
                type: 'vbox',
                align: 'center',
                pack: 'center'
            },
            items: [
                {
                    xtype: 'toolbar',
                    width: '100%',
                    style: 'background: transparent',
                    items: [
                        {
                            xtype: 'panel',
                            items: [
                                {
                                    xtype: 'button',
                                    tooltip: 'Scarica Documento',
                                    iconCls: 'x-fa fa-download',
                                    cls: 'mybutton',
                                    handler: function () {
                                        var colletion_id = CL.app.getController("C_event_file").event_id;
                                        var file_id = CL.app.getController("C_event_file").file_id;

                                        Ext.create('Ext.Component', {
                                            renderTo: Ext.getBody(),
                                            cls: 'x-hidden',
                                            autoEl: {
                                                tag: 'iframe',
                                                src: 'data/event_file/download_single.php?file_id='+file_id+'&event_id='+colletion_id
                                            }
                                        });
                                    }
                                }
                            ]
                        },
                        {
                            xtype: 'panel',
                            items: [
                                {
                                    xtype: 'button',
                                    tooltip: 'Condividi Documento',
                                    icon: 'images/icons/icon_share.png',
                                    cls: 'mybutton',
                                    handler: function () {
                                        var targetEl = this,
                                            event_id = CL.app.getController("C_event_file").event_id,
                                            file_id = CL.app.getController("C_event_file").file_id,
                                            store = Ext.create("CL.store.S_event_file");

                                        store.load({
                                            params:{
                                                event_id: event_id,
                                                file_id: file_id
                                            },
                                            callback: function () {
                                                var rec = this.getAt(0);
                                                CL.app.getController("C_event_file").share(targetEl,rec);

                                            }
                                        });
                                    }
                                }
                            ]
                        },
                        '->',
                        {
                            xtype: 'panel',
                            bodyStyle: {
                                background: "transparent"
                            },
                            layout: {
                                type: 'hbox',
                                align: 'center',
                                pack: 'center'
                            },
                            items: [
                                {
                                    xtype: 'label',
                                    style: {
                                        background: "transparent"
                                    },
                                    html: '<img src="images/icons/icon_preview.png" alt=" " style="width:50px;height:50px;">'
                                },
                                {
                                    xtype: 'label',
                                    text:'title',
                                    name: 'title',
                                    style: {
                                        color: 'white',
                                        fontSize: 'xx-large',
                                        fontWeight: 'bold'
                                    }
                                }
                            ]
                        },
                        /*
                        {
                            style: {
                                background: "transparent",
                                borderColor: "transparent"
                            },
                            html: '<img src="images/icons/icon_preview.png" alt=" " style="width:50px;height:50px;margin-left: -10px; margin-top: 10px;">'
                        },
                        {
                            xtype: 'label',
                            text:'title',
                            name: 'title',
                            style: {
                                color: 'white',
                                fontSize: 'xx-large',
                                fontWeight: 'bold'
                            }
                        },*/
                        '->',
                        {
                            xtype: 'panel',
                            items: [
                                {
                                    xtype: 'button',
                                    tooltip: 'Elimina event_file',
                                    iconCls: 'x-fa fa-trash',
                                    cls: 'mybutton',
                                    handler: function(){

                                        var event_id = CL.app.getController("C_event_file").event_id,
                                            file_id = CL.app.getController("C_event_file").file_id,
                                            store = Ext.create("CL.store.S_event_file");

                                        store.load({
                                            params:{
                                                event_id: event_id,
                                                file_id: file_id
                                            },
                                            callback: function () {
                                                var rec = this.getAt(0);
                                                CL.app.getController("C_permessi").canWriteEvent(event_id, true,function(){
                                                    Ext.Msg.confirm('Attenzione!', 'Eliminare <b>'+rec.get("title")+"</b>?",function(btn){
                                                        if (btn === 'yes'){
                                                            //console.log(rec);
                                                            //Ext.StoreManager.lookup("S_event_file").remove(rec);
                                                            //alert("ATTENZIONE! Anche se lo store ha l'autoSync, quest'ultimo non scatta dopo il remove. Probabilmente perchè quel 'rec' non è un record/modello")
                                                            Ext.Ajax.request({
                                                                url: 'data/event_file/destroy.php',
                                                                params: {
                                                                    data: Ext.JSON.encode(rec.data)
                                                                }
                                                            });
                                                            CL.app.getController("C_event_file").redirectTo("event/"+rec.get("event_id"));
                                                        }

                                                    });
                                                });

                                            }
                                        });
                                    }
                                }
                            ]
                        },
                        {
                            xtype: 'panel',
                            layout: 'hbox',
                            items: [
                                {
                                    xtype: 'button',
                                    tooltip: "Modifica Info",
                                    iconCls: 'x-fa fa-pencil',
                                    action: 'on_edit_info',
                                    cls: 'mybutton',
                                    handler: function(){

                                        var event_id = CL.app.getController("C_event_file").event_id,
                                            file_id = CL.app.getController("C_event_file").file_id,
                                            store = Ext.create("CL.store.S_event_file");

                                        store.load({
                                            params:{
                                                event_id: event_id,
                                                file_id: file_id
                                            },
                                            callback: function () {
                                                var rec = this.getAt(0);
                                                CL.app.getController("C_permessi").canWriteEvent(event_id, true,function(){
                                                    CL.app.getController("C_event_file").onEdit(rec);
                                                });

                                            }
                                        });
                                    }
                                }
                            ]
                        }

                    ]
                },
                {
                    xtype: 'label',
                    text:'description',
                    name: 'description',
                    style: {
                        color: 'white',
                        fontSize: 'medium'
                    },
                    margin: '20 0 0 0'
                },
                {
                    xtype: 'label',
                    text: 'created_by_name',
                    name: 'created_by_name',
                    style: {
                        color: '#963232',
                        fontSize: 'medium',
                        fontWeight: 'bold'
                    },
                    margin: '20 0 0 0'
                },
                {
                    xtype: 'label',
                    text: 'data_chiusura',
                    name: 'data_chiusura',
                    //html: 'Data di chiusura delle modifiche: <u>24-06-2016 16:00</u>',
                    style: {
                        color: 'white',
                        fontSize: 'medium'
                    },
                    margin: '20 0 0 0'
                },
                {html:'<br>'}
            ]
        },

        {
            xtype: 'panel',
            height: 700,
            width: '100%',
            margin: '10 0 10 0',
            layout: {
                type: 'hbox',
                align: 'center',
                pack: 'center'
            },
            bodyStyle: {
                background: "#333333"
            },
            items:[
                {
                    xtype: 'panel',
                    padding: 10,
                    flex: 1,
                    bodyStyle: {
                        background: 'url(images/no_preview.jpg)',
                        backgroundSize: 'cover'
                    },
                    height: '100%',
                    layout: 'fit',
                    name: 'preview'/*,
                    listeners:{
                        render: function (panel) {
                            panel.el.on('mouseenter', function () {
                                console.log("mouseenter per evitare lo scrolling del corpo principale mentre scrollo sull'imageviewer");
                                Ext.ComponentQuery.query("viewport panel[name=scrollable]")[0].setOverflowXY(false,false);
                            });
                            panel.el.on('mouseleave', function () {
                                console.log("mouseleave per evitare lo scrolling del corpo principale mentre scrollo sull'imageviewer");
                                Ext.ComponentQuery.query("viewport panel[name=scrollable]")[0].setOverflowXY(false,"scroll");
                            });
                        }
                    }*/
                }
            ]
        }
    ]

});
