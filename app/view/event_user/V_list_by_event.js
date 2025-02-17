Ext.define('CL.view.event_user.V_list_by_event', {
    extend: 'Ext.panel.Panel',
    xtype: 'event_user_list_by_event',
    itemId: 'event_user_list_by_event_id',
    alias: 'widget.event_user_list_by_event',

    title: 'Collaboratori',
    icon: 'images/icons/icon_collaboratori.png',
    bodyStyle: 'background: #484848',
    padding: 1,

    listeners: {
        activate: function(){
            Ext.StoreManager.lookup("S_event_user").load({
                params:{
                    event_id: (window.location.hash.split("/"))[1]
                }
            });

            var event_id = (window.location.hash.split("/"))[1];
            CL.app.getController("C_event").redirectTo("event/"+event_id+"/coworkers");
        }
    },


    layout: 'fit',

    items: [
        {
            xtype: 'grid',
            name:'event_user',
            store: 'S_event_user',
            //hideHeaders: true,
            tbar: [
                {
                    xtype: 'panel',
                    items: [
                        {
                            xtype: 'button',
                            text: 'Aggiungi Collaboratore',
                            icon: 'images/icons/icon_add_user.png',
                            action: 'aggiungi_collaboratore',
                            margin: '0 5 0 0',
                            listeners: {
                                click: function (btn) {
                                    var event_id = (window.location.hash.split("/"))[1];

                                    CL.app.getController("C_permessi").canWriteCollaboratoriEvent(event_id, true,function(){
                                        CL.app.getController("C_event_user").onCreate(btn.el);
                                    });

                                }
                            }
                        },
                        {
                            xtype: 'button',
                            text: 'Aggiungi gruppo di Collaboratori',
                            icon: 'images/icons/icon_add_group.png',
                            action: 'aggiungi_gruppo_collaboratori',
                            listeners: {
                                click: function (btn) {
                                    var event_id = (window.location.hash.split("/"))[1];

                                    CL.app.getController("C_permessi").canWriteCollaboratoriEvent(event_id, true,function(){
                                        CL.app.getController("C_event_user").onCreateGroup(btn.el)
                                    });
                                }
                            }
                        }
                    ]
                },
                '->',
                {
                    xtype: 'label',
                    margin: '0 10 0 10',
                    html: '<a onclick="CL.app.getController(\'C_event_user\').showInfo(this);return false;" href="#" style="color: #963232 !important; font-weight: bold; font-size: large;" ><u>Chi/Cosa sono i Collaboratori?</u></a>'
                }
            ],
            columns: [
                {
                    sortable: false,
                    dataIndex: 'type',
                    flex: 0.7,
                    renderer: function(value, metaData, record){
                        if(record.get("icon_url") == "")
                            return "";
                        else
                            return '<img src="'+record.get("icon_url")+'" title="'+record.get("icon_tooltip")+'" alt=" " height="20" width="20">';
                    }
                },
                {
                    text: 'Nome',
                    dataIndex: 'user_name',
                    flex: 10,
                    renderer: function(value, metaData, record){
                        return '<a style="color: #963232 !important;" target="_blank" href="http://192.168.1.6/Cerini/KMS/#user/'+record.get("user_id")+'"><u>'+value+' (#'+record.get("user_id")+')</u></a>';
                    }
                },
                {
                    text: 'Gruppo',
                    dataIndex: 'group_name',
                    flex: 10
                },
                {
                    text: 'Ente d\'Appartenenza',
                    dataIndex: 'affiliation_name',
                    flex: 10
                },
                {
                    xtype: 'actioncolumn',
                    width: 75,
                    items: [
                        {
                            iconCls: 'x-fa fa-trash',
                            tooltip: 'Elimina',
                            handler: function(grid, rowIndex) {
                                var rec = grid.getStore().getAt(rowIndex);
                                var event_id = (window.location.hash.split("/"))[1];

                                CL.app.getController("C_permessi").canWriteCollaboratoriEvent(event_id, true,function(){
                                    if(rec.get("icon_tooltip") == "Creatore dell'Evento")
                                        Ext.Msg.alert("Attenzione!","Non è possibile rimuovere il <b>creatore</b> dell'Evento!");
                                    else{
                                        Ext.Msg.confirm('Attenzione!', 'Rimuovere il collaboratore <b>'+rec.get("user_name")+"</b>?",function(btn){
                                            if (btn === 'yes'){
                                                Ext.StoreManager.lookup("S_event_user").remove(rec);
                                                Ext.StoreManager.lookup("S_event_user").sync();
                                                setTimeout(function(){
                                                    Ext.toast({
                                                        title: 'Successo',
                                                        html: 'Collaboratore rimosso correttamente!',
                                                        align: 'br'
                                                    });
                                                }, 500);
                                            }
                                        });
                                    }
                                });

                            }
                        },
                        {
                            icon: 'images/icons/icon_medal.png',
                            tooltip: 'Promuovi a Gestore Collaboratori',
                            handler: function(grid, rowIndex) {
                                var rec = grid.getStore().getAt(rowIndex);
                                var event_id = (window.location.hash.split("/"))[1];

                                CL.app.getController("C_permessi").canWriteCollaboratoriEvent(event_id, true,function(){
                                    Ext.Msg.confirm('Attenzione!', 'Eleggere come Gestore dei Collaboratori <b>'+rec.get("user_name")+"</b>?<br>Ricorda che può esserci <u>un solo gestore alla volta</u>.",function(btn){
                                        if (btn === 'yes'){
                                            rec.set({is_coworker_manager: true});
                                            Ext.StoreManager.lookup("S_event_user").sync({
                                                callback: function () {
                                                    Ext.StoreManager.lookup("S_event_user").reload();

                                                    setTimeout(function(){
                                                        Ext.toast({
                                                            title: 'Successo',
                                                            html: 'Collaboratore promosso correttamente!',
                                                            align: 'br'
                                                        });
                                                    }, 500);
                                                }
                                            });
                                        }
                                    });
                                });

                            }
                        }
                    ]
                }
            ]
        }
    ]

});
