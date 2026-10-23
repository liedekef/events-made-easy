package generator

import (
	"bytes"
	"fmt"
	"maps"
	"os"
	"path/filepath"
	"slices"

	"github.com/iancoleman/strcase"
)

//nolint:unused // retained for future SumUp class generation
var reservedServiceNames = map[string]struct{}{
	"Authorization": {},
}

//nolint:unused // writeSumUpClass is kept for the legacy SumUp SDK surface
func (g *Generator) writeSumUpClass() error {
	dir := g.cfg.Out
	if err := os.MkdirAll(dir, os.ModePerm); err != nil {
		return fmt.Errorf("create SumUp directory: %w", err)
	}

	filename := filepath.Join(dir, "SumUp.php")
	f, err := os.OpenFile(filename, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o644)
	if err != nil {
		return fmt.Errorf("open %q: %w", filename, err)
	}
	defer func() {
		_ = f.Close()
	}()

	services := g.collectServiceDefinitions()

	var buf bytes.Buffer
	buf.WriteString(`<?php

namespace SumUp;

`)

	for _, useStmt := range sumUpUseStatements(services) {
		fmt.Fprintf(&buf, "use %s;\n", useStmt)
	}

	buf.WriteString("\n")
	buf.WriteString(`/**
 * Class SumUp
 *
 * @package SumUp
 */
class SumUp
{
    /**
     * The access token for API authentication.
     *
     * @var string|null
     */
    protected $accessToken;

    /**
     * @var HttpClientInterface
     */
    protected $client;

`)

	buf.WriteString(`
    /**
     * SumUp constructor.
     *
     * @param string|array|null $configOrApiKey
     *
     * @throws SDKException
     */
    public function __construct($configOrApiKey = null)
    {
        $config = [];
        if (is_string($configOrApiKey) && $configOrApiKey !== '') {
            $config['api_key'] = $configOrApiKey;
        } elseif (is_array($configOrApiKey)) {
            $config = $configOrApiKey;
        }
        $customHttpClient = $config['client'] ?? null;
        if (array_key_exists('client', $config)) {
            unset($config['client']);
        }
        $config = $this->normalizeConfig($config);
        if ($customHttpClient instanceof HttpClientInterface) {
            $this->client = $customHttpClient;
        } else {
            $this->client = new CurlClient(
                $config['base_uri'],
                $config['custom_headers'],
                $config['ca_bundle_path']
            );
        }
        if (!empty($config['api_key'])) {
            $this->accessToken = $config['api_key'];
        } elseif (!empty($config['access_token'])) {
            $this->accessToken = $config['access_token'];
        }
    }

    /**
     * Returns the default access token.
     *
     * @return string|null
     */
    public function getDefaultAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Normalize configuration and apply defaults.
     *
     * @param array $config
     *
     * @return array
     *
     * @throws ConfigurationException
     */
    private function normalizeConfig(array $config)
    {
        $config = array_merge([
            'api_key' => null,
            'access_token' => null,
            'base_uri' => 'https://api.sumup.com',
            'custom_headers' => [],
            'ca_bundle_path' => null,
        ], $config);

        if ($config['api_key'] === null) {
            $config['api_key'] = getenv('SUMUP_API_KEY') ?: null;
        }

        if ($config['access_token'] === null) {
            $config['access_token'] = getenv('SUMUP_ACCESS_TOKEN') ?: null;
        }

        $headers = is_array($config['custom_headers']) ? $config['custom_headers'] : [];
        $headers['Accept'] = 'application/problem+json, application/json';
        $headers['User-Agent'] = SdkInfo::getUserAgent();
        $config['custom_headers'] = $headers;

        return $config;
    }

`)

	for _, service := range services {
		method := strcase.ToLowerCamel(service)
		buf.WriteString("    /**\n")
		fmt.Fprintf(&buf, "     * Access the %s API endpoints.\n", service)
		buf.WriteString("     *\n")
		fmt.Fprintf(&buf, "     * @return %s\n", service)
		buf.WriteString("     *\n")
		buf.WriteString("     * @throws ConfigurationException\n")
		buf.WriteString("     */\n")
		fmt.Fprintf(&buf, "    public function %s()\n", method)
		buf.WriteString("    {\n")
		buf.WriteString("        if (empty($this->accessToken)) {\n")
		buf.WriteString("            throw new ConfigurationException('No access token provided');\n")
		buf.WriteString("        }\n\n")
		fmt.Fprintf(&buf, "        return new %s($this->client, $this->accessToken);\n", service)
		buf.WriteString("    }\n\n")
	}

	buf.WriteString(`
}
`)

	if _, err := f.Write(buf.Bytes()); err != nil {
		return fmt.Errorf("write SumUp file %q: %w", filename, err)
	}

	return nil
}

//nolint:unused // helper is used when SumUp class generation is re-enabled
func (g *Generator) collectServiceDefinitions() []string {
	if len(g.operationsByTag) == 0 {
		return nil
	}

	tagKeys := slices.Collect(maps.Keys(g.operationsByTag))
	slices.Sort(tagKeys)
	services := make([]string, 0, len(tagKeys))

	for _, tagKey := range tagKeys {
		if tagKey == sharedTagKey {
			continue
		}

		operations := g.operationsByTag[tagKey]
		if len(operations) == 0 {
			continue
		}

		className := g.displayTagName(tagKey)
		if _, reserved := reservedServiceNames[className]; reserved {
			continue
		}

		services = append(services, className)
	}

	return services
}

//nolint:unused // helper for the SumUp class code generation
func sumUpUseStatements(serviceNames []string) []string {
	uses := []string{
		"SumUp\\Exception\\ConfigurationException",
		"SumUp\\Exception\\SDKException",
		"SumUp\\HttpClient\\CurlClient",
		"SumUp\\HttpClient\\HttpClientInterface",
		"SumUp\\SdkInfo",
	}

	serviceSet := map[string]struct{}{}

	for _, name := range serviceNames {
		serviceSet[fmt.Sprintf("SumUp\\Services\\%s", name)] = struct{}{}
	}

	serviceUses := slices.Collect(maps.Keys(serviceSet))
	slices.Sort(serviceUses)

	return append(uses, serviceUses...)
}
