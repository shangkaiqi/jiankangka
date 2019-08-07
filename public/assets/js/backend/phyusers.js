define(
		[ 'jquery', 'bootstrap', 'backend', 'table', 'form' ],
		function($, undefined, Backend, Table, Form) {

			var Controller = {
				index : function() {
					// 初始化表格参数配置
					Table.api.init({
						extend : {
							index_url : 'phyusers/index',
							add_url : 'phyusers/add',
							edit_url : 'phyusers/edit',
							del_url : 'phyusers/del',
							multi_url : 'phyusers',
							table : 'physical_users',
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
											sortable : true,
											operate : false
										},
										{
											field : 'type',
											title : '类别',
											formatter : Table.api.formatter.label,
											searchList : {
												1 : __('团队'),
												0 : __('个人'),
												2 : __('临时')
											}
										},
										{
											field : 'identitycard',
											title : '身份证'
										},
										{
											field : 'name',
											title : "姓名"
										},
										{
											field : 'sex',
											title : "性别",
											formatter : Table.api.formatter.label,
											searchList : {
												1 : __('女'),
												0 : __('男')
											}
										},
										{
											field : 'phone',
											title : '联系方式'
										},
										{
											field : 'registertime',
											title : '体检时间'
										},
										{
											field : 'order_serial_number',
											title : '体检编号'
										},
										{
											field : 'bussname',
											title : '体检单位'
										},
										{
											field : 'employee',
											title : '从业类别'
										},
										{
											field : 'physic_status',
											title : '体检结果',
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
										/*{
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
												$(table).data("operate-edit",
														null);
												that.table = table;
												return Table.api.formatter.operate
														.call(that, value, row,
																index);
											},
										}*/ ] ]
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