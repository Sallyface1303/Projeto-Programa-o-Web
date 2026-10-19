# Validação W3C

Validador: **Nu Html Checker (vnu) 26.10.12** — o mesmo motor do validator.w3.org, rodando localmente.
Para CSS foi usada a opção `--also-check-css`.

```
java -jar vnu.jar --format text JogoWebV1/*.html
java -jar vnu.jar --also-check-css --format text JogoWebV1/*.css
```

| Tipo | Arquivos | Resultado |
|------|----------|-----------|
| HTML | 10 (cadastro, edicao, global_score, jogo, login, main_menu, perfil, score, selecao, titulo) | Nenhum erro ou aviso |
| CSS  | 11 (style, style_jogo, style_mainmenu, style_titlescr, style_perfil, style_login_cadastro, layout_edicao, layout_perfil, layout_ranking, layout_score, layout_selecao) | Nenhum erro ou aviso |

Checagem extra: nenhum link, imagem ou `location.href` local aponta para arquivo inexistente.

Para conferir também no site oficial: https://validator.w3.org/ (aba "Validate by File Upload") e https://jigsaw.w3.org/css-validator/ (aba "By file upload").
