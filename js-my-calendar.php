<script>


var DJonCalendar = {

	TargetObj: null,
	yearMin: 2000,
	yearMax: 2041,

	// Opens a calendar
	open: function (TargetID) {

		if (document.getElementById('djon_calendar')) { // Закрываем, если открыт календарь
			this.close();
		} else {
			TargetObj = document.getElementById(TargetID);
			var dLeft = 0;
			var dTop = 0;
			CalendarDiv = document.createElement('DIV');
			CalendarDiv.setAttribute('id', 'djon_calendar');
			var Content = '';
			// Кнопка "Сегодня"
			Content += '<div class="btnToday" onClick="DJonCalendar.setToday()">Сегодня</div>';

			Content += '<table border="0" cellpadding=0 cellspacing=0 width="100%"><tr>';
			// Мясяц назад
			Content += '<td class="btnControl"><div onClick="DJonCalendar.setMonth(-1)"><</div></td>';
			// Выбор месяца
			Content += '<td><SELECT class="selects" id="month_djon_calendar" onchange="DJonCalendar.display(this.selectedIndex, Year.selectedIndex+' + this.yearMin + ')">';
			Content += '<option>Январь<option>Февраль<option>Март<option>Апрель<option>Май<option>Июнь<option>Июль<option>Август <option>Сентябрь<option>Октябрь<option>Ноябрь<option>Декабрь</SELECT></td>';
			// Выбор года
			Content += '<td><SELECT id="year_djon_calendar" class="selects" onchange="DJonCalendar.display(Month.selectedIndex,this.selectedIndex+' + this.yearMin + ')">';
			for (var i = this.yearMin; i < this.yearMax; i++) Content += "<option>" + i;
			Content +='</SELECT></td>';
			// Месяц вперед
			Content +='<td class="btnControl"><div onClick="DJonCalendar.setMonth(1)">></div></td>';
			Content += '</tr></table>';


			// Календарь
			Content +='<table id="cal_table">';
			// Дни недели
			Content +='<thead><tr><th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th class="header_rest">Сб</th><th class="header_rest">Вс</th></tr></thead>';
			// Ячейки для чисел
			Content +='<tbody>';
			for (var i = 0; i < 6; i++) {
				Content += "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
			}
			Content +="</tbody></table>";

			CalendarDiv.innerHTML = Content;
			CalendarDiv.style.position = 'absolute';
			CalendarDiv.style.left = this.calcLeft(TargetObj) + dLeft + 'px';
			CalendarDiv.style.top = this.calcTop(TargetObj) + TargetObj.offsetHeight + dTop + 'px';
			document.body.appendChild(CalendarDiv);
			CalTable = document.getElementById('cal_table');
			Month = document.getElementById('month_djon_calendar');
			Year = document.getElementById('year_djon_calendar');
			
			// Считываем день, месяц и год с поля input
			if (TargetObj) {
				var x_day = parseInt(TargetObj.value.substr(0,2), 10);
				var x_month = parseInt(TargetObj.value.substr(3,2), 10);
				var x_year = parseInt(TargetObj.value.substr(6,4), 10);
				if (x_day > 0 && x_day <= this.getDaysInMonth(x_month, x_year) && x_month > 0 && x_month < 13 && x_year > this.yearMin && x_year < this.yearMax){
					day = x_day;
					Month.selectedIndex = x_month - 1;
					Year.selectedIndex = x_year - this.yearMin;
					this.display(x_month - 1, x_year, x_day);
				} else this.setToday();
			} else this.setToday();
		}
	},

	// Закрываем календарь
	close: function () {
		document.body.removeChild(CalendarDiv);
	},

	// показ календаря
	display: function (x_month, x_year, x_day) {
		x_month = parseInt(x_month, 10);
		x_year = parseInt(x_year, 10);
		var days = this.getDaysInMonth(x_month + 1, x_year);
		// определяем количество дней в предыдущем месяце
		if (x_month > 1) {
			var days_before = this.getDaysInMonth(x_month, x_year);
		} else {
			var days_before = this.getDaysInMonth(12, x_year - 1);
		}
		if (day > days) day = days;
		if (!x_day) x_day = day;
		var curr_day = 0;
		var firstOfMonth = new Date (x_year, x_month, 1);
		
		// определяем день недели для 1-го числа текущего месяца
		var startingPos = firstOfMonth.getDay();
		// меняем для воскресенья позицию с 0 на 7
		if (startingPos == 0) startingPos = 7;
		// переназначаем позицию на понедельник, чтобы показывалась неделя предыдущего месяца (аналогично и для вторника)
		if (startingPos == 1) startingPos = 8;
		if (startingPos == 2) startingPos = 9;

		for (i = 0; i < 42; i++) {
			curr_day = i - startingPos + 2;
			// назначаем класс будням и выходным
			if (CalTable.rows[Math.floor(i/7)+1].cells[i%7].cellIndex > 4) {
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].className = "day_rest";
			} else {
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].className = "day";
			}

			if (curr_day <= 0 ) {
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].innerHTML = curr_day + days_before;
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].className = "day_disabled";
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].onmouseover = "";
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].onmouseout = "";
			} else if (curr_day > 0 && curr_day <= days) {
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].innerHTML = curr_day;
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].onmouseover = this.eventHandlerOver;
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].onmouseout = this.eventHandlerOut;
			} else if (curr_day > days) {
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].innerHTML = curr_day - days;
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].className = "day_disabled"
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].onmouseover = "";
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].onmouseout = "";
			}
			CalTable.rows[Math.floor(i/7)+1].cells[i%7].onclick = this.eventHandlerClick;
			if (curr_day == x_day) {
				CalTable.rows[Math.floor(i/7)+1].cells[i%7].className = "day_selected";
			}
		}
	},

	// Событие "наведение мыши на дату"
	eventHandlerOver: function (anEvObj) {
		if (this.className != "day_selected") {
			this.className = "day_mouseover";
		}
	},

	// Событие "мышь отведена с даты"
	eventHandlerOut : function (anEvObj) {
		if (this.className != "day_selected") {
			if (this.cellIndex > 4) {
				this.className = "day_rest";
			} else {
				this.className = "day";
			}
		}
	},

	// Обработчик клика (запись даты в поле)
	eventHandlerClick: function (anEvObj) {
		var str_day = "";
		var str_month = Month.selectedIndex + 1;
		var str_year = Year.selectedIndex + DJonCalendar.yearMin;
		day = this.innerHTML;
		if (this.className == "day_disabled") {
			if (parseInt(day, 10) > 20) {
				DJonCalendar.setMonth(-1);
			} else {
				DJonCalendar.setMonth(1);
			}
		} else {
			if (day.toString().length == 1) {
				str_day = "0" + day;
			} else {
				str_day = day;
			}
			if (str_month < 10) {
				str_month = "0" + str_month;
			}
			TargetObj.value = (str_day + "." + str_month + "." + str_year);

			// Делаем в формате 2021-11-15T03:00:00+03:00
			var dateFormat;
			
			if ($(TargetObj).is('#filter-start-date')) {
				dateFormat = str_year + "-" + str_month + "-" + str_day + "T00:00:00+03:00";
				$('#selected-start-date').val(dateFormat);
			} else if ($(TargetObj).is('#filter-end-date')) {
				dateFormat = str_year + "-" + str_month + "-" + str_day + "T23:59:59+03:00";
				$('#selected-end-date').val(dateFormat);
			}

			DJonCalendar.close();
		}
	},

	// Перемещение по годам 
	setYear: function (val) {
		if (!isNaN(val)) {
			x_year = Year.selectedIndex + this.yearMin;
			x_year = Number(x_year) + val;
			if (x_year < this.yearMin) {
				x_year = this.yearMin;
			}
			if (x_year > 2099) {
				x_year = 2099;
			}
			Year.selectedIndex = x_year - this.yearMin;
			this.display(Month.selectedIndex, Year.selectedIndex + this.yearMin);
		}
	},

	// Перемещения по месяцам
	setMonth: function (val) {
		if (!isNaN(val)) {
			var i = 0;
			var x_month = Month.selectedIndex;
			i = x_month + val;
			x_month = i%12;
			if (x_month < 0) {
				x_month = x_month + 12;
			}
			Month.selectedIndex = x_month;
			this.setYear(Math.floor(i/12));
			this.display(Month.selectedIndex, Year.selectedIndex + this.yearMin);
		}
	},

	// Переход к текущему дню
	setToday: function () {
		var now = new Date();
		var x_day = now.getDate();
		var x_month = now.getMonth();
		var x_year = now.getFullYear();
		day = x_day;
		Month.selectedIndex = x_month;
		Year.selectedIndex = x_year - this.yearMin;
		this.display(Month.selectedIndex, Year.selectedIndex + this.yearMin, x_day);
	},

	// Количество дней в месяце
	getDaysInMonth: function (x_month,x_year) {
		var days;
		if (x_month==1 || x_month==3 || x_month==5 || x_month==7 || x_month==8 || x_month==10 || x_month==12) {
			days = 31;
		} else if (x_month==4 || x_month==6 || x_month==9 || x_month==11) {
			days = 30;
		} else if (x_month == 2) {
			if (this.isLeapYear(x_year)) {
				days = 29;
			} else {
				days = 28;
			}
		}
		return (days);
	},

	// Проверка на високосный год
	isLeapYear: function (x_year) {
		if (((x_year % 4) == 0) && ((x_year % 100) != 0) || ((x_year % 400) == 0)) {
			return (true);
		} else {
			return (false);
		}
	},

	// Расчет положения слева
	calcLeft: function (Obj) {
		var x_ret = 0;
		var oParent = Obj.offsetParent;
		if (oParent == null) {
			return 0;
		} else {
			x_ret = Obj.offsetLeft + this.calcLeft(oParent);
		}
		return x_ret;
	},

	// Расчет положения сверху
	calcTop: function (Obj) {
		var x_ret = 0;
		var oParent = Obj.offsetParent;
		if (oParent == null) {
			return 0;
		} else {
			x_ret = Obj.offsetTop + this.calcTop(oParent);
		}
		return x_ret;
	}
}

</script>
