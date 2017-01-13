// stdWrap functions being applied to each element
lib.gridelements.defaultGridSetup {
	columns {
		default {
			renderObj = COA
			renderObj {
				# You can use registers to i.e. provide different image settings for each column
				# 10 = LOAD_REGISTER
				20 =< tt_content
				# And you can reset the register later on
				# 30 = RESTORE_REGISTER
			}
		}
		#2 < .default
		#2 {
		#}
	}
	# if you want to provide your own templating, just insert a cObject here
	# this will prevent the collected content from being rendered directly
	# i.e. cObject = TEMPLATE or cObject = FLUIDTEMPLATE will be available from the core
	# the content will be available via fieldnames like
	# tx_gridelements_view_columns (an array containing each column)
	# or tx_gridelements_view_children (an array containing each child)
	# tx_gridelements_view_column_123 (123 is the number of the column)
	# or tx_gridelements_view_child_123 (123 is the UID of the child)
}

lib.tt_content.shortcut.pages = COA
lib.tt_content.shortcut.pages {
	10 = USER
	10 {
		userFunc = GridElementsTeam\Gridelements\Plugin\Gridelements->user_getTreeList
	}
	20 = CONTENT
	20 {
		table = tt_content
		select {
			pidInList.data = register:pidInList
			where = colPos >= 0
			languageField = sys_language_uid
			orderBy = colPos,sorting
			orderBy.dataWrap = FIND_IN_SET(pid,'{register:pidInList}'),|
		}
	}
}

tt_content.shortcut {
	5 = LOAD_REGISTER
	5 {
		tt_content_shortcut_recursive.field = recursive
	}
	20 {
		tables := addToList(pages)
		conf.pages < lib.tt_content.shortcut.pages
	}
	30 = RESTORE_REGISTER
}

plugin.tx_gridelements_pi1 >
tt_content.gridelements_pi1 >
tt_content.gridelements_pi1 = COA
tt_content.gridelements_pi1 {
	10 = CASE
	10 {
		key.field = section_frame
		default = TEXT
		default.value = <div id="c{field:uid}" class="frame frame-type-grid">
		default.insertData = 1
		1 =< tt_content.gridelements_pi1.10.default
		1.value = <div id="c{field:uid}" class="frame invisible">
		5 =< tt_content.gridelements_pi1.10.default
		5.value = <div id="c{field:uid}" class="frame rulerbefore">
		6 =< tt_content.gridelements_pi1.10.default
		6.value = <div id="c{field:uid}" class="frame rulerafter">
		10 =< tt_content.gridelements_pi1.10.default
		10.value = <div id="c{field:uid}" class="frame col-xs-10 col-xs-push-1">
		11 =< tt_content.gridelements_pi1.10.default
		11.value = <div id="c{field:uid}" class="frame col-xs-9 col-xs-push-3">
		12 =< tt_content.gridelements_pi1.10.default
		12.value = <div id="c{field:uid}" class="frame col-xs-9">
		29 =< tt_content.gridelements_pi1.10.default
		29.value = <div id="c{field:uid}" class="frame well">
		21 =< tt_content.gridelements_pi1.10.default
		21.value = <div id="c{field:uid}" class="frame jumbotron">
		22 =< tt_content.gridelements_pi1.10.default
		22.value = <div id="c{field:uid}" class="frame no-vertical-margin">
		23 =< tt_content.gridelements_pi1.10.default
		23.value = <div id="c{field:uid}" class="frame full-width">
	}

	20 = COA
	20 {
		10 = USER
		10 {
			userFunc = GridElementsTeam\Gridelements\Plugin\Gridelements->main
			setup {
				default < lib.gridelements.defaultGridSetup
			}
		}
	}

	30 = TEXT
	30.value = </div>
}

tt_content.gridelements_view < tt_content.gridelements_pi1
