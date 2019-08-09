define(
		[ 'jquery', 'bootstrap', 'backend', 'table', 'form' ],
		function($, undefined, Backend, Table, Form) {

			var Controller = {
				index : function() {
					// 初始化表格参数配置
					Table.api.init({
						extend : {
							index_url : 'result/perspective//index'
									+ location.search,
							add_url : 'result/perspective//add',
							edit_url : 'result/perspective//edit',
							del_url : 'result/perspective//del',
							multi_url : 'result/perspective//multi',
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
											operate : false
										},
										{
											field : 'name',
											title : "姓名",
											operate : 'LIKE %...%',
											placeholder : '模糊搜索，*表示任意字符'
										},
										{
											field : 'identitycard',
											title : '身份证'
										},
										{
											field : 'sex',
											title : '性别',
											operate : false,
											formatter : Table.api.formatter.label,
											searchList : {
												1 : __('女'),
												0 : __('男')
											}
										},
										{
											field : 'age',
											title : '年龄',
											operate : false
										},
										{
											field : 'phone',
											title : '手机号',
											operate : false
										},
										{
											field : 'order_serial_number',
											title : '体检编号'
										},
										{
											field : 'employee',
											title : '从业类别',
											operate : false
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
											field : 'physical_result',
											title : '结果',
											operate : false,
											formatter : function(value, row) {
												if (value === 0)
													return '<span style="font-size:14px;color:#000">合格</span>';
												if (value === 1)
													return '<span class="label" style="font-size:14px;color:red">异常</span>';
												if (value === 2)
													return '<span class="label" style="font-size:14px;color:red">未录入结果</span>';
											},
											searchList : {
												0 : __('合格'),
												1 : __('异常')
											}
										},
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
												that.table = table;
												return Table.api.formatter.operate
														.call(that, value, row,
																index);
											}
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
						Fast.api.ajax({
							type : 'GET',
							url : "result/perspective/mulit",
							data : {
								'id' : basic
							},
						}, function(data, ret) {
							// 成功的回调
							table.bootstrapTable('refresh');
							return ret.msg;
						}, function(data, ret) {
							table.bootstrapTable('refresh');
							return ret.msg;
						});
					});

				},
				add : function() {
					Controller.api.bindevent();
				},
				edit : function() {
					Controller.api.bindevent();
				},
				withdraw : function() {
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