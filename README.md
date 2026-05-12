# 🎬 Sistema de Recomendação de Filmes com IA

## 📋 Sobre o Projeto

Sistema web completo para recomendação de filmes utilizando **Inteligência Artificial baseada em gêneros**. O sistema aprende com as avaliações dos usuários e sugere filmes personalizados com base nos seus gêneros favoritos.

### 🎯 Funcionalidades

| Funcionalidade | Descrição |
|----------------|-----------|
| 🔐 Autenticação | Registro, login, logout e recuperação de sessão |
| 🎬 Catálogo de Filmes | Integração com TMDb API - filmes populares e busca |
| ⭐ Avaliações | CRUD completo de notas (1 a 5 estrelas) |
| 🤖 IA de Recomendação | Algoritmo baseado em gêneros favoritos do usuário |
| 🌓 Modo Escuro/Claro | Alternância entre temas com persistência local |
| 🌐 Internacionalização | Suporte a Português (PT) e Inglês (EN) |
| 📊 Exportação | Exportação de histórico em CSV/PDF |
| 📱 Responsivo | Interface adaptada para desktop, tablet e mobile |


## 🧠 Como Funciona a IA de Recomendação

O sistema utiliza um **algoritmo de filtro baseado em conteúdo**:

1. **Coleta** todos os filmes que o usuário avaliou com nota ≥ 4
2. **Analisa** os gêneros desses filmes e calcula pesos (frequência)
3. **Busca** filmes não avaliados pelo usuário
4. **Calcula** score de similaridade baseado nos gêneros
5. **Ordena** por score + popularidade
6. **Explica** ao usuário por que cada filme foi recomendado

**Exemplo de funcionamento:**
- Usuário avaliou "Super Mario" (Aventura, Animação, Fantasia) com nota 5
- Usuário avaliou "Como Mágica" (Aventura, Animação, Fantasia) com nota 5
- IA detecta que gêneros favoritos: Aventura (100%), Animação (100%), Fantasia (100%)
- Recomenda filmes com esses mesmos gêneros

## 🛠️ Tecnologias Utilizadas

### Frontend
| Tecnologia | Versão | Finalidade |
|------------|--------|------------|
| Angular | 18+ | Framework principal |
| TailwindCSS | 3.4+ | Estilização moderna |
| ngx-translate | 15+ | Internacionalização |
| Chart.js | 4.4+ | Gráficos estatísticos |
| jspdf + html2canvas | - | Exportação PDF |

### Backend
| Tecnologia | Versão | Finalidade |
|------------|--------|------------|
| PHP | 8.3+ | API RESTful |
| MySQL | 8.0+ | Banco de dados relacional |
| TMDb API | v3 | Dados reais de filmes |

## 🚀 Como Executar o Projeto

### Pré-requisitos

| Software | Versão | Download |
|----------|--------|----------|
| PHP | 8.3+ | https://www.php.net/downloads |
| MySQL | 8.0+ | https://www.mysql.com/downloads |
| Node.js | 20+ | https://nodejs.org |
| Angular CLI | 18+ | `npm install -g @angular/cli` |
| Composer (opcional) | - | https://getcomposer.org |

### Passo 1: Configurar o Banco de Dados

```bash
# Acessar phpMyAdmin ou MySQL Workbench
# Executar o script:
database/schema.sql

# Entrar na pasta do backend
cd backend

# Iniciar o servidor PHP
php -S localhost:8000

# O servidor estará disponível em:
# http://localhost:8000/api/auth.php?action=me

# Entrar na pasta do frontend
cd frontend

# Instalar dependências
npm install

# Iniciar o servidor Angular
ng serve --open

# O aplicativo estará disponível em:
# http://localhost:4200 

## Também é necessário:

Crie uma conta em: https://www.themoviedb.org

Vá em Configurações → API

Crie uma chave de desenvolvedor

Substitua a chave em backend/api/movies.php:

define('TMDB_API_KEY', 'SUA_CHAVE_AQUI');
