define(
		[ 'jquery', 'bootstrap', 'backend', 'table', 'form' ],
		function($, undefined, Backend, Table, Form) {

			var Controller = {
				index : function() {
					// 初始化表格参数配置
					Table.api.init({
						extend : {
							index_url : 'service/search//index'+ location.search,
							add_url : 'service/search//add',
							edit_url : 'service/search//edit',
							del_url : 'service/search//del',
							multi_url : 'service/search//multi',
							table : 'order',
						}
					});

					var table = $("#table");

					// 初始化表格
					table
							.bootstrapTable({

								url : $.fn.bootstrapTable.defaults.extend.index_url,
								pk : 'id',
								sortName : 'id',
								// 禁用默认搜索
								search : false,
								// 启用普通表单搜索
								commonSearch : true,
								// 可以控制是否默认显示搜索单表,false则隐藏,默认为false
								searchFormVisible : true,
								columns : [ [
										{
											checkbox : true
										},
										{
											field : 'id',
											title : 'Id',
											operate : false
										},
										{
											field : 'name',
											title : "姓名"
										},
										{
											field : 'phone',
											title : '手机号',
											operate : false
										},
										{
											field : 'identitycard',
											title : '身份证',
											operate : false
										},
										{
											field : 'order.order_serial_number',
											title : '登记号码'
										},
										{
											field : 'employee',
											title : '从业类别',
											operate : false
										},
										{
											field : 'type',
											title : '体检类别',
											formatter : Table.api.formatter.label,
											searchList : {
												1 : __('团队'),
												0 : __('个人'),
												2 : __('临时')
											}
										},
										{
											// field : 'order.create_date',
											field : 'registertime',
											title : '体检时间',
											operate : 'RANGE',
											addclass : 'datetimerange',
											formatter : Table.api.formatter.datetime

										},
										{
											field : 'order.obtain_employ_number',
											title : '健康证号',
											operate : false
										},
										// {
										// 	field : 'company',
										// 	title : '体检单位',
										// 	operate : 'LIKE %...%',
										// 	placeholder : '请输入体检单位'
										// },

										{
											field : 'order.order_status',
											title : '体检状态',
											formatter : Table.api.formatter.label,
											searchList : {
												0 : __('未体检'),
												1 : __('体检中'),
												2 : __('已体检'),
												3 : __('已出卡')
											}
										},
										{
											field : 'order.physical_result',
											title : '体检结果',
											operate : false,
											formatter : function(value, row) {
												if (value === 1)
													return '<span style="font-size:14px;color:#000">合格</span>';
												if (value === 0)
													return '<span class="label" style="font-size:14px;color:red">异常</span>';
											},
											searchList : {
												1 : __('合格'),
												0 : __('异常')
											}
										},
                                    // {
                                    //     field : 'operate',
                                    //     title : __('Operate'),
                                    //     table : table,
                                    //     events : Table.api.events.operate,
                                    //     formatter : Table.api.formatter.operate,
                                    // },


										{
											field : 'operate',
											title : __('Operate'),
											table : table,
											events : Table.api.events.operate,
											formatter : function(value, row,
													index) {
												var that = $.extend({}, this);
												var table = $(that.table)
														.clone(true);
												$(table).data("operate-del",
														null);
												$(table).data("operate-edit",
														null);
												that.table = table;
												return Table.api.formatter.operate
														.call(that, value, row,
																index);
											},
											buttons : [
													{
														name : 'nav_table',
														text : __('详情'),
														// icon: 'fa fa-list',
														classname : 'btn btn-xs btn-primary fuyandan btn-addtabs',
														//url : '/index/service/search/info?id={ids}'
														url:'service/search/info?id={ids}'
													} ],
										} ] ]
							});




					// 为表格绑定事件
					Table.api.bindevent(table);

					// 获取选中项
					$(document).on("click", ".btn-selected", function() {
						var rows = table.bootstrapTable('getSelections');
						var str = '';
						for (var i = 0; i < rows.length; i++) {
							str += rows[i]['id'] + ",";
						}
						basic = str.substr(0, str.length - 1);
					    window.location.href = "/index/service/search/printMulit?id="+basic;
					});
					// 获取选中项
					$(document).on("click", ".expUser", function() {
						var rows = table.bootstrapTable('getSelections');
						var str = '';
						for (var i = 0; i < rows.length; i++) {
							str += rows[i]['id'] + ",";
						}
						basic = str.substr(0, str.length - 1);
					    window.location.href = "/index/service/search/expUser?id="+basic;

					});
					// 获取选中项
// 					$(document).on("click", ".fuyandan", function() {
// //						Layer.alert("aaaaaaaaaaaa");
// //						var rows = table.bootstrapTable('getSelections');
// //						var str = '';
// //						for (var i = 0; i < rows.length; i++) {
// //							str += rows[i]['ids'] + ",";
// //						}
// //						basic = str.substr(0, str.length - 1);
// //					    window.location.href = "/admin/service/search/expUser?id="+basic;
//
// 					});

				},
				add : function() {
					Controller.api.bindevent();
				},
				edit : function() {
					Controller.api.bindevent();
				},
				api : {
					bindevent : function() {
						Form.api.bindevent($("form[role=form]"));
					},
				}
			};
			return Controller;


		});