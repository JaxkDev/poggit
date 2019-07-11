if has("autocmd")
	augroup tmpl
		autocmd BufNewFile *.rs 0r .vim/template.rs
	augroup END
endif

command Build w | !cargo build

let g:sql_type_default = 'pgsql'