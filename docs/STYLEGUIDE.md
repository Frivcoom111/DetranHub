# Styleguide - DetranHub

Este documento resume a paleta de cores, variáveis e utilitários disponíveis no projeto.

## Paleta (definida em `css/theme.css`)
- `--bg`: #f3f6fb — fundo claro da aplicação
- `--surface`: #ffffff — superfícies / cards
- `--text`: #0f1724 — cor principal do texto
- `--muted`: #6b7280 — texto secundário / legendas
- `--primary`: #2563eb — cor principal (botões, links ativos)
- `--success`: #16a34a — sucesso / confirm
- `--danger`: #dc2626 — erro / atenção
- `--accent`: #7c3aed — cor de destaque / alterna

## Variáveis úteis
- `--shadow-sm`: sombra principal para cards

## Componentes e classes
- `.card-surface` — card com fundo e sombra padrão.
- `.btn-primary` — botão primário (usa `--primary`).
- `.btn-outline-primary` — versão outline do primário.
- `.text-muted` — texto secundário (usa `--muted`).
- `.bg-muted` — fundo sutil usando `--muted-bg`.
- `.focus-ring` — utilitário para foco acessível.

## Regras de uso
- Use as variáveis de `theme.css` como fonte única de verdade para cores/spacing. Evite duplicar `:root` em outros arquivos CSS.
- Para adicionar novas cores, atualize `css/theme.css` e, se necessário, documente aqui.

## Exemplo de uso
```html
<button class="btn btn-primary">Salvar</button>
<div class="card-surface p-3">Conteúdo</div>
```

## Acessibilidade
- Botões primários têm contraste suficiente com o fundo.
- Indicadores de foco (`.focus-ring`) ajudam na navegação por teclado.

---

Arquivo gerado automaticamente pelo assistente. Ajuste a paleta conforme necessário.
