<template>
	<tr
		class="block-header"
		:class="{
			'animation-add':	is_animation_add,
			'animation-del':	is_animation_del,
			'animation-down':	is_animation_down,
			'animation-up':		is_animation_up
		}"
		:style="{
			'--block_1_height':		block_1_height,
			'--block_2_height':		block_2_height,
			'--zoom_block_time':	zoom_block_time,
			'--move_block_time':	move_block_time
		}"
	>
		<td colspan="6" class="block-name">
			<input
				type="text"
				name="noname"
				placeholder="Наименование сборочного узла"
				v-model="this.prop_BLOCK.block_name"
				@blur="updatedBlockNameBlur"
			>
			<input
				type="number"
				min="1"
				max="1000000"
				name="noname"
				v-model="this.prop_BLOCK.quantity"
				@blur="updatedBlockQuantityBlur"
			>
			<input
				type="text"
				name="noname"
				value="шт."
				readonly
			>
		</td>
		<td>
			<div style="float: right;">
				<img
					:src="'public/assets/img/ico/move_down_ico.png'"
					class="ico-1"
					title="Опустить"
					@click="onClick('moveBlockDown')"
				/>
				<img
					:src="'public/assets/img/ico/move_up_ico.png'"
					class="ico-1"
					title="Поднять"
					@click="onClick('moveBlockUp')"
				/>
				<img
					:src="'public/assets/img/ico/' + ico_mark_name"
					class="ico-1"
					title="Включить в расчет"
					@click="onClick('changeMarkBlock')"
				/>
				<img
					:src="'public/assets/img/ico/' + ico_collapse_name"
					class="ico-1"
					title="Свернуть/Развернуть сборочный узел"
					@click="onClick('changeBlockCollapse')"
				/>
				<img 
					:src="'public/assets/img/ico/add_ico.png'"
					class="ico-1"
					title="Добавить новый сборочный узел"
					@click="onClick('addBlock')"
				/>
				<img
					:src="'public/assets/img/ico/copy_ico.png'"
					class="ico-1"
					title="Копировать сборочный узел"
					@click="onClick('copyBlock')"
				/>
				<img
					:src="'public/assets/img/ico/delete_ico.png'"
					class="ico-1"
					:class="{ invisible: prop_BLOCK.btn_delblock_invisible }"
					title="Удалить сборочный узел"
					@click="onClick('openPopupForDeleteBlock')"
				/>
			</div>
		</td>
	</tr>

	<calc-row
		v-if="!this.prop_BLOCK.collapse"
		:class="{
			'animation-add':	is_animation_add,
			'animation-del':	is_animation_del,
			'animation-down':	is_animation_down,
			'animation-up':		is_animation_up
		}"
		v-for="(row, index) in prop_BLOCK.rows"
		:key="index"
		:prop_ROW="row"
		:prop_index_block="prop_index_block"
		:prop_index_row="index"
		@emit-on-click="emitOnClick"
		:style="{
			'--block_1_height':		block_1_height,
			'--block_2_height':		block_2_height,
			'--zoom_block_time':	zoom_block_time,
			'--move_block_time':	move_block_time
		}"
	/>
	<!-- v-if+v-for не рекомендуется, но в данном случае работает корректно, а другой способ пока не найден -->
</template>


<script>
	import CalcRow from "./row/CalcRow.vue";

	export default {

		components: {CalcRow},
		props: {
			prop_BLOCK:			Object,
			prop_index_block:	String
		},
		emits: [
			'emitOnClick'
		],
		data() {
			return {
				emit_BUS: {
					index_block:	this.prop_index_block,
					return_action:	null,
					return_value:			null
				}
			}
		},
		computed: {
			ico_collapse_name()	{ return this.prop_BLOCK.collapse ? 'expand_ico.png' : 'collapse_ico.png'	},
			ico_mark_name()		{ return this.prop_BLOCK.mark ? 'tick_on_ico.png' : 'tick_off_ico.png'		},
			is_animation_add()	{ return this.prop_BLOCK.is_animation_add	},
			is_animation_del()	{ return this.prop_BLOCK.is_animation_del	},
			is_animation_down()	{ return this.prop_BLOCK.is_animation_down	},
			is_animation_up()	{ return this.prop_BLOCK.is_animation_up	},
			block_1_height()	{ return this.$root.ANIMATION_DATA.block_1_height	},
			block_2_height()	{ return this.$root.ANIMATION_DATA.block_2_height	},
			zoom_block_time()	{ return this.$root.ANIMATION_DATA.zoom_block_time/1000 + 's'	},
			move_block_time()	{ return this.$root.ANIMATION_DATA.move_block_time/1000 + 's'	}
		},
		methods: {
			emitOnClick(emit_BUS) {
				this.$emit('emitOnClick', emit_BUS)
			},
			onClick(action_name) {
				this.emit_BUS.return_action = action_name
				this.$emit('emitOnClick', this.emit_BUS)
			},
			updatedBlockNameBlur(e) {
				this.emit_BUS.return_value			= e.target.value
				this.emit_BUS.return_action	= 'updatedBlockNameBlur'
				this.$emit('emitOnClick', this.emit_BUS)
				this.emit_BUS.return_value			= null
			},
			updatedBlockQuantityBlur(e) {
				this.emit_BUS.return_value	= this.validationQuantity(e.target.value)
				this.emit_BUS.return_action	= 'updatedBlockQuantityBlur'
				this.$emit('emitOnClick', this.emit_BUS)
				this.emit_BUS.return_value	= null
			},
			validationQuantity(value) {
				value = String(value)
				value = value.replaceAll('-', '')
				value = value.replaceAll('+', '')
				value = value.replaceAll('.', '')
				value = value.replaceAll(',', '')
				value = value.replaceAll('e', '')
				value = Number(value)
				if(!value) value = 1
				if(value > 1000000) value = 1000000
				return value
			}
		}
	}
</script>


<style lang="scss" scoped>
	.block-header td {
		padding: 18px 0 0 0;
	}

	.block-name input {
		font-size: 18px;
		font-weight: 600;
		text-align: center;
		border: none;
		outline: none;	/*убираем обводку при наведении*/
		padding: 3px 0px 3px 0px;
	}
	.block-name input:nth-child(1) {
		width: 83%;
		border-radius: 3px 0 0 3px;
		text-align: left;
		padding-left:70px;
		border: 1px solid #000;
		border-right: none;
	}
	.block-name input:nth-child(2) {
		width: 12%;
		text-align: right;
		border: 1px solid #000;
		border-left: none;
		border-right: none;
	}
	.block-name input:nth-child(3) {
		width: 5%;
		border-radius: 0 3px 3px 0;
		border: 1px solid #000;
		border-left: none;
	}

	::placeholder {
		color: rgb(182, 182, 182);
		font-weight: 400;
	}

	
	// ДЛЯ АНИМАЦИЙ
	.animation-add {
		animation: zoomIn var(--zoom_block_time) cubic-bezier(0.4, 0.3, 0.2, 1.1) both;
	}
	@keyframes zoomIn {
		from	{ opacity: 0;	transform: scale(0.5);	}
		to		{ opacity: 1;	transform: scale(1);	}
	}
		// как вариант, расширение по высоте:	transform: rotateX(-90deg);		transform: rotateX(0);

	.animation-del {
		animation: zoomOut var(--zoom_block_time) cubic-bezier(0.4, 0.3, 0.2, 1.1) both;
	}
	@keyframes zoomOut {
		from	{ opacity: 1;	transform: scale(1);	}
		to		{ opacity: 0;	transform: scale(0.5);	}
	}


	.animation-down {
		animation: moveDown var(--move_block_time) cubic-bezier(0.4, 0.3, 0.2, 1.0) both;
	}
	@keyframes moveDown {
		from	{ transform: translateY(0); }
		to		{ transform: translateY(var(--block_2_height)); }
	}

	.animation-up {
		animation: moveUp var(--move_block_time) cubic-bezier(0.4, 0.3, 0.2, 1.0) both;
	}
	@keyframes moveUp {
		from	{ transform: translateY(0); }
		to		{ transform: translateY(calc(var(--block_1_height) * -1)); }
	}

</style>



