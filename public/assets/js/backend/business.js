define(
		[ 'jquery', 'bootstrap', 'backend', 'table', 'form' ],
		function($, undefined, Backend, Table, Form) {

			var Controller = {
				index : function() {
					// 初始化表格参数配置
					Table.api.init({
						extend : {
							index_url : 'business/index' + location.search,
							add_url : 'business/add',
							edit_url : 'business/edit',
							del_url : 'business/del',
							multi_url : 'business/multi',
							table : 'business',
						}
					});

					var table = $("#table");

					// 初始化表格
					table
							.bootstrapTable({
								url : $.fn.bootstrapTable.defaults.extend.index_url,
								pk : 'bs_id',
								sortName : 'bs_id',
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
											field : 'bs_id',
											title : __('Id'),
											operate : false
										},
										{
											field : 'busisess_name',
											title : __('Busisessname'),
											operate : 'LIKE %...%',
											placeholder : '模糊搜索，*表示任意字符'
										},
										{
											field : 'createtime',
											title : __('Createtime'),
											operate : 'RANGE',
											addclass : 'datetimerange',
											formatter : Table.api.formatter.datetime
										},
										{
											field : 'phone',
											title : __('Phone'),
											operate : false
										},
										{
											field : 'physical_num',
											title : __('制卡量'),
											operate : false
										},
										{
											field : 'profession',
											title : __('Profession'),
											operate : false
										},
										{
											field : 'area',
											title : __('Area'),
											operate : false
										},
										{
											field : 'address',
											title : __('Address')
										},
										{
											field : 'charge',
											title : __('Charge'),
											operate : false
										},
										{
											field : 'bus_num',
											title : __('总体检数量'),
											operate : false
										},
										{
											field : 'health',
											title : __('卫生安全体检量'),
											operate : false
										},
										{
											field : 'medicine',
											title : __('食药健康体检量'),
											operate : false
										},
										{
											field : 'operate',
											title : __('Operate'),
											table : table,
											events : Table.api.events.operate,
											// formatter :
											// Table.api.formatter.operate,
											formatter : function(value, row,
													index) {
												var that = $.extend({}, this);
												var table = $(that.table)
														.clone(true);
												$(table).data("operate-del",
														null);
												that.table = table;
												return Table.api.formatter.operate
														.call(that, value, row,
																index);
											},
											/*buttons : [ {
												name : 'nav_table',
												text : __('添加管理员'),
												// icon: 'fa fa-list',
												classname : 'btn btn-xs btn-primary fuyandan btn-addtabs',
												url : '/admin/service/search/printword?id={ids}'
											} ],*/
										} ] ]
							});

					// 为表格绑定事件
					Table.api.bindevent(table);
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
					}
				}
			};
			return Controller;
		});