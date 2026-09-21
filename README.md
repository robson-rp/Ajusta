<p align="center">
  <img src="resources/static/img/brand/ajusta-horizontal.webp" alt="AJUSTA" width="360">
</p>

<p align="center">
  <strong>Facturação e salários, ajustados à lei angolana.</strong><br>
  Uma conta. Um NIF. Um ciclo.
</p>

> [!NOTE]
> **AJUSTA** é baseado no [InvoiceShelf](https://github.com/InvoiceShelf/InvoiceShelf),
> software livre sob a licença [AGPL-3.0](LICENSE). O AJUSTA mantém a mesma licença:
> o código-fonte desta versão modificada está disponível neste repositório.
> A interface está em Português de Angola (com inglês disponível), usa o Kwanza e as
> predefinições angolanas, e tem identidade visual própria.

> [!WARNING]
> Este fork segue o ramo `3.x` do InvoiceShelf, que é uma versão de pré-lançamento (alpha).
> Não o use com dados de produção.

## Run your invoicing from one place

InvoiceShelf is a self-hosted web application for creating invoices, tracking
payments and expenses, and keeping customer accounts organised. It is built for
freelancers and small businesses that want a focused workflow without giving up
control of their data.

- Create invoices and estimates, then export polished PDFs.
- Record payments and see what each customer still owes.
- Track expenses, taxes, and business reports.
- Schedule recurring invoices for repeat work.
- Give customers a portal for invoices, estimates, and payment history.
- Manage multiple companies and invite team members with scoped roles.

Optional official modules can add specialised features without making the core
application heavier.

## Install InvoiceShelf

### Production: InvoiceShelf 2.x

Install the current stable release from the
[self-hosted download page](https://invoiceshelf.com/download), or run the
official Docker image with the `:latest` tag. Follow the
[installation guide](https://docs.invoiceshelf.com/installation.html) for the
complete setup and upgrade instructions.

### Preview: InvoiceShelf 3.x

Use the preview only with disposable or backed-up data:

- Download the latest 3.x preview from the
  [self-hosted download page](https://invoiceshelf.com/download).
- For Docker, use `invoiceshelf/invoiceshelf:next` instead of `:latest` in the
  [official Compose setup](https://github.com/InvoiceShelf/docker).

A minimal SQLite Docker setup looks like this:

```bash
git clone https://github.com/InvoiceShelf/docker.git invoiceshelf
cd invoiceshelf
cp docker-compose.sqlite.yml docker-compose.yml
# For the 3.x preview, change the image tag in docker-compose.yml to :next.
docker compose up -d
```

Open <http://localhost:8090> and finish the setup wizard. Read the
[Docker guide](https://docs.invoiceshelf.com/install/docker.html) before using
InvoiceShelf on a public server.

For a traditional web-server installation, see the
[manual installation guide](https://docs.invoiceshelf.com/install/manual.html).
InvoiceShelf 3.x requires PHP 8.4 and supports MySQL/MariaDB, PostgreSQL, and
SQLite. Docker includes the required application runtime.

## Learn and get help

- [User and installation documentation](https://docs.invoiceshelf.com/)
- [API reference](https://api-docs.invoiceshelf.com/)
- [Discord community](https://discord.gg/eHXf4zWhsR)
- [Bug reports and feature requests](https://github.com/InvoiceShelf/InvoiceShelf/issues)

## Contribute

Code contributions are welcome. Start with the
[contribution guide](CONTRIBUTING.md) and use the development environment in
[`docker/development`](docker/development/README.md).

You can also help translate InvoiceShelf on
[Crowdin](https://crowdin.com/project/invoiceshelf).

## License

InvoiceShelf is released under the
[GNU Affero General Public License v3.0](LICENSE).
