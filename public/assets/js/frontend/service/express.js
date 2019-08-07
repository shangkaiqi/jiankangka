define([ 'jquery', 'bootstrap', 'backend', 'table', 'form' ], function($,
		undefined, Backend, Table, Form) {

	var Controller = {
		index : function() {
			// 初始化表格参数配置
			Table.api.init({
				extend : {
					index_url : 'service/express//index' + location.search,
					add_url : 'service/express//add',
					edit_url : 'service/express//edit',
					del_url : 'service/express//del',
					multi_url : 'service/express//multi',
					table : 'order',
				}
			});

			var table = $("#table");

			// 初始化表格
			table.bootstrapTable({

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
							operate: 'LIKE %...%', 
							placeholder: '模糊搜索，*表示任意字符'
						},
						{
							field : 'phone',
							title : '手机号',
						},
						{
							field : 'order.obtain_employ_number',
							title : '健康正号',
							operate : false
						},
						{
							field : 'order.express_status',
							title : '状态',
							formatter: Table.api.formatter.label,
							searchList: {1: __('已下单'), 0: __('待下单')}
						},
						{
							field : 'order.addr',
							title : '收货地址',
							operate : false
						},
						{
							field : 'order.express_num',
							title : '快递单号'
						},
						{
							field : 'operate',
							title : __('Operate'),
							table : table,
							events : Table.api.events.operate,
							formatter : function(value, row, index) {
								var that = $.extend({}, this);
								var table = $(that.table).clone(true);
								$(table).data("operate-del", null);
								that.table = table;
								return Table.api.formatter.operate.call(that,
										value, row, index);
							}
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
			},
		}
	};
	return Controller;
});