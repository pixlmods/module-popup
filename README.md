# PixlMods_Popup

Módulo Magento 2 para criar e gerenciar popups configuráveis no frontend, com regras de exibição (página, loja, grupo de clientes, data, gatilho, frequência) e relatório de engajamento (views, fechamentos, conversões).

## Requisitos

- Magento 2.4+
- PHP 8.1+
- Módulos: `Magento_Ui`, `Magento_Admin`, `Magento_Cms` (fonte de páginas/lojas), `Magento_Customer` (grupos de clientes)

## Instalação

```bash
bin/magento module:enable PixlMods_Popup
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Uso

### Admin

**Marketing > Popup > Popup Settings** (`pixlmods/popup/index`): CRUD dos popups.

Campos principais:

| Campo | Descrição |
|---|---|
| Title | Nome interno do popup (não exibido no frontend) |
| Content | Conteúdo HTML/WYSIWYG exibido dentro do modal |
| Status | Enabled/Disabled |
| Pages | Páginas onde o popup pode ser exibido (páginas de plataforma por handle, ou páginas CMS ativas) |
| Store View | Lojas onde o popup é elegível |
| Customer Groups | Grupos de clientes elegíveis (vazio = todos os grupos) |
| Start/End Date | Janela de vigência (opcional) |
| Trigger Type | Quando o popup tenta aparecer: On Page Load, Exit Intent, Scroll Percentage, Time on Page, Click on Element |
| Trigger Value | Parâmetro do gatilho (percentual de scroll, segundos, ou seletor CSS, conforme o tipo) |
| Display Delay | Atraso extra, em segundos, antes do gatilho ser armado |
| Frequency | Regra de repetição por visitante: Always, Once per Session, Once per Day, Never Show Again After Closing |
| Priority | Em caso de múltiplos popups elegíveis na mesma página, o de menor número é exibido primeiro (só um popup aparece por carregamento de página) |

**Marketing > Popup > Popup Reports** (`pixlmods/report/index`): grid somente leitura com Views, Closes, Conversions e Conversion Rate por popup.

Conversão é contada quando o visitante clica em qualquer link/botão dentro do conteúdo do popup.

### Frontend

O bloco `PixlMods\Popup\Block\Frontend\Popup` é injetado no container `content` de todas as páginas (`view/frontend/layout/default.xml`) e filtra, no servidor, os popups elegíveis para a página/loja/grupo/data atuais. O JS (`PixlMods_Popup/js/view/popup`) decide, no cliente, qual desses popups mostrar primeiro com base no gatilho e na regra de frequência (cookie/sessionStorage), e reporta eventos (`view`/`close`/`conversion`) via `pixlmodspopup/report/event`.

## Configuração

**Stores > Settings > Configuration > General > Popup** (`pixlmods_popup/*`):

| Campo | Path | Descrição |
|---|---|---|
| Enable Popup Module | `pixlmods_popup/general/active` | Liga/desliga a exibição de popups na loja inteira, sem desativar cada popup individualmente |
| Enable Event Tracking | `pixlmods_popup/tracking/enabled` | Liga/desliga a gravação de eventos (view/close/conversion) usados no relatório |
| "Once per Day" Cookie Duration (days) | `pixlmods_popup/tracking/day_frequency_days` | Quantos dias o cookie da frequência "Once per Day" dura |

Todos os campos têm valor padrão (módulo habilitado, tracking habilitado, 1 dia) definido em `etc/config.xml`, então não é necessário configurar nada após a instalação.

## ACL

- `PixlMods_Popup::popup` — acesso ao CRUD de popups
- `PixlMods_Popup::report` — acesso ao relatório
- `PixlMods_Popup::config` — acesso à tela de configuração

## Estrutura de dados

- `pixlmods_popup` — configuração de cada popup
- `pixlmods_popup_event` — log de eventos (view/close/conversion) por popup, usado no relatório

## Testes

Testes unitários em `Test/Unit`, rodáveis com o PHPUnit do próprio projeto Magento (não depende de `dev/tests/unit`):

```bash
cd app/code/PixlMods/Popup
php ../../../../vendor/bin/phpunit -c phpunit.xml.dist
```

Cobrem a lógica de elegibilidade de popup (`Block\Frontend\Popup`), o endpoint de tracking (`Controller\Report\Event`) e o model de configuração (`Model\Config`). Ainda não há testes de Integration (precisam do harness `dev/tests/integration`, que este projeto não inclui).

## Limitações conhecidas

- Sem testes de Integration (só Unit).
- Sem suporte a geo-targeting, valor mínimo de carrinho ou parâmetros de campanha (UTM/referrer) como condição de exibição.
- O tracking de eventos não verifica consentimento de cookies (LGPD/GDPR) antes de gravar frequência via cookie.

## Licença

MIT — veja [LICENSE](LICENSE).
