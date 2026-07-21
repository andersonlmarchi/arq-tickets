import { useState } from 'react'
import { Link } from 'react-router-dom'
import SwaggerUI from 'swagger-ui-react'
import 'swagger-ui-react/swagger-ui.css'
import './DocsPage.css'

const SPECS = [
  {
    id: 'sales',
    name: 'Vendas (sales-service)',
    url: '/openapi/sales.openapi.json',
  },
  {
    id: 'catalog',
    name: 'Catalogo (catalog-service)',
    url: '/openapi/catalog.openapi.json',
  },
] as const

export default function DocsPage() {
  const [activeUrl, setActiveUrl] = useState<string>(SPECS[0].url)

  return (
    <div className="docs-layout">
      <header className="docs-header">
        <div>
          <h1>API - Swagger</h1>
        </div>
        <Link className="docs-back" to="/">
          Voltar a vitrine
        </Link>
      </header>
      <div className="docs-tabs" role="tablist" aria-label="APIs">
        {SPECS.map((spec) => (
          <button
            key={spec.id}
            type="button"
            role="tab"
            aria-selected={activeUrl === spec.url}
            className={activeUrl === spec.url ? 'active' : undefined}
            onClick={() => setActiveUrl(spec.url)}
          >
            {spec.name}
          </button>
        ))}
      </div>
      <SwaggerUI key={activeUrl} url={activeUrl} deepLinking />
    </div>
  )
}
